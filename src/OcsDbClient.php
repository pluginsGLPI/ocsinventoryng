<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

 https://github.com/pluginsGLPI/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 ocsinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocsinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Ocsinventoryng;

use AllowDynamicProperties;
use DBmysql;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QueryIdentifier;

/**
 * Class OcsDbClient
 */
#[AllowDynamicProperties]
class OcsDbClient extends OcsClient
{
    /**
     * Join of the computers of the OCS database on their administrative information
     */
    private const HARDWARE_ACCOUNTINFO_JOIN = [
        'accountinfo' => [
            'ON' => [
                'hardware'    => 'ID',
                'accountinfo' => 'HARDWARE_ID',
            ],
        ],
    ];

    /**
     * Criteria excluding the archived computers (OCS 2.8 and later)
     *
     * @param mixed $version GUI_VERSION config of the OCS database
     *
     * @return array
     */
    private static function getArchiveCriteria($version): array
    {
        if ($version != false && $version['TVALUE'] >= OcsServer::OCS2_8_VERSION_LIMIT) {
            return ['hardware.ARCHIVE' => null];
        }

        return [];
    }

    /**
     * Criteria of the FILTER option of countComputers() / getComputers()
     *
     * @param array $options
     *
     * @return array
     */
    private static function getComputerFilterCriteria(array $options): array
    {
        if (!isset($options['FILTER']) || !is_array($options['FILTER'])) {
            return [];
        }
        $filters = $options['FILTER'];
        $where   = [];

        if (!empty($filters['IDS'])) {
            $where[] = ['hardware.ID' => ['IN', array_map('intval', (array) $filters['IDS'])]];
        }
        if (!empty($filters['EXCLUDE_IDS'])) {
            $where[] = ['hardware.ID' => ['NOT IN', array_map('intval', (array) $filters['EXCLUDE_IDS'])]];
        }
        if (!empty($filters['DEVICEIDS'])) {
            $where[] = ['hardware.DEVICEID' => ['IN', array_map('strval', (array) $filters['DEVICEIDS'])]];
        }
        if (!empty($filters['EXCLUDE_DEVICEIDS'])) {
            $where[] = ['hardware.DEVICEID' => ['NOT IN', array_map('strval', (array) $filters['EXCLUDE_DEVICEIDS'])]];
        }
        if (!empty($filters['TAGS'])) {
            $where[] = ['accountinfo.TAG' => ['IN', array_map('strval', (array) $filters['TAGS'])]];
        }
        if (!empty($filters['EXCLUDE_TAGS'])) {
            $where[] = ['accountinfo.TAG' => ['NOT IN', array_map('strval', (array) $filters['EXCLUDE_TAGS'])]];
        }
        if (!empty($filters['CHECKSUM'])) {
            // Computers with at least one of the wanted sections modified,
            // and inventoried since the given date if any
            $where[] = new QueryExpression(
                '(' . (int) $filters['CHECKSUM'] . ' & ' . DBmysql::quoteName('hardware.CHECKSUM') . ') > 0',
            );
            if (!empty($filters['INVENTORIED_SINCE'])) {
                $where[] = ['hardware.LASTDATE' => ['>', self::checkFilterDate($filters['INVENTORIED_SINCE'])]];
            }
        } elseif (!empty($filters['INVENTORIED_SINCE']) && !isset($filters['CHECKSUM'])) {
            $where[] = ['hardware.LASTDATE' => ['>', self::checkFilterDate($filters['INVENTORIED_SINCE'])]];
        }
        if (!empty($filters['INVENTORIED_BEFORE'])) {
            $where[] = ['hardware.LASTDATE' => ['<', self::checkFilterDate($filters['INVENTORIED_BEFORE'])]];
        }

        return $where;
    }

    /**
     * A date filter (INVENTORIED_SINCE / INVENTORIED_BEFORE) put in the SQL of the OCS database:
     * a date "Y-m-d" or "Y-m-d H:i:s" only, the epoch otherwise
     *
     * @param mixed $date
     *
     * @return string
     */
    private static function checkFilterDate($date): string
    {
        $date = is_scalar($date) ? trim((string) $date) : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $date)) {
            return $date;
        }

        return '1970-01-01 00:00:00';
    }

    /**
     * The ORDER / OFFSET / MAX_RECORDS options put in the SQL of the OCS database:
     * a known column for the sort, integers for the pagination
     *
     * @param array  $options
     * @param string $default_order
     *
     * @return array [ORDER, LIMIT, START] criteria of DBmysql::request(), 0 for no LIMIT / START
     */
    private static function checkQueryOptions(array $options, string $default_order): array
    {
        $order = $default_order;
        if (isset($options['ORDER'])
            && preg_match('/^\s*(LASTDATE|NAME|ID)(?:\s+(ASC|DESC))?\s*$/i', (string) $options['ORDER'], $matches)) {
            $order = strtoupper($matches[1]) . (isset($matches[2]) ? ' ' . strtoupper($matches[2]) : '');
        }
        $max_records = isset($options['MAX_RECORDS']) ? max(0, (int) $options['MAX_RECORDS']) : 0;
        $offset      = isset($options['OFFSET']) ? max(0, (int) $options['OFFSET']) : 0;

        return [$order, $max_records, $offset];
    }

    /**
     * The custom value of an accountinfo field (config ACCOUNT_VALUE_<column>_<value>),
     * the stored value otherwise
     *
     * @param string $column accountinfo column (fields_N)
     * @param mixed  $value
     *
     * @return mixed
     */
    private function getAccountInfoCustomValue($column, $value)
    {
        if (is_null($value)
            || !preg_match('/^fields_(\d+)$/', $column, $matches)
            || !$this->OcsTableExists("accountinfo_config")) {
            return $value;
        }
        $colname = $this->db->request([
            'SELECT' => ['ID', 'NAME'],
            'FROM'   => 'accountinfo_config',
            'WHERE'  => ['ID' => (int) $matches[1]],
        ])->current();
        if (empty($colname['NAME'])) {
            return $value;
        }
        // Values from the OCS agents: bound parameter
        $name         = "ACCOUNT_VALUE_" . $colname['NAME'] . "_" . $value;
        $custom_value = $this->db->request([
            'SELECT' => ['TVALUE', 'NAME'],
            'FROM'   => 'config',
            'WHERE'  => ['NAME' => ['=', $name]],
        ])->current();

        return $custom_value['TVALUE'] ?? $value;
    }

    /**
     * @var DBocs
     */
    private $db;

    /**
     * OcsDbClient constructor.
     *
     * @param $id
     * @param $dbhost
     * @param $dbuser
     * @param $dbpassword
     * @param $dbdefault
     */
    public function __construct($id, $dbhost, $dbuser, $dbpassword, $dbdefault)
    {
        parent::__construct($id);

        $this->dbname = $dbdefault;

        try {
            $this->db = new DBocs($dbhost, $dbuser, $dbpassword, $dbdefault);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Failed to connect to OCS database on host "%s": %s', $dbhost, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return DBocs
     */
    public function getDB()
    {
        return $this->db;
    }

    /**
     * Check if database connection is established
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->db && $this->db->connected;
    }

    public function getSchema()
    {
        return $this->dbname;
    }

    /**
     * @param       $id
     * @param array $tables
     *
     * @return array
     */
    public function getComputerRule($id, $tables = [])
    {
        // Put in the SQL of the OCS database: integers only
        $id = (int) $id;
        if (!$this->isConnected()) {
            throw new \RuntimeException('OCS database connection is not established');
        }

        $computers = [];

        $version = $this->getConfig("GUI_VERSION");

        $request = $this->db->request([
            'SELECT'     => ['hardware.*', 'accountinfo.TAG'],
            'FROM'       => 'hardware',
            'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
            'WHERE'      => ['hardware.ID' => $id] + self::getArchiveCriteria($version),
        ]);
        foreach ($request as $meta) {
            $computers["META"]["ID"]       = $meta["ID"];
            $computers["META"]["CHECKSUM"] = $meta["CHECKSUM"];
            $computers["META"]["DEVICEID"] = $meta["DEVICEID"];
            $computers["META"]["LASTCOME"] = $meta["LASTCOME"];
            $computers["META"]["LASTDATE"] = $meta["LASTDATE"];
            $computers["META"]["NAME"]     = $meta["NAME"];
            $computers["META"]["TAG"]      = $meta["TAG"];
            $computers["META"]["USERID"]   = $meta["USERID"];
            $computers["META"]["UUID"]     = $meta["UUID"];
        }

        $request = $this->db->request([
            'FROM'  => 'hardware',
            'WHERE' => ['ID' => $id],
        ]);
        foreach ($request as $hardware) {
            $computers[strtoupper('hardware')] = $hardware;
        }

        foreach ($tables as $table) {
            if ($table == "accountinfo") {
                $request = $this->db->request([
                    'FROM'  => 'accountinfo',
                    'WHERE' => ['HARDWARE_ID' => $id],
                ]);
                foreach ($request as $accountinfo) {
                    foreach ($accountinfo as $column => $value) {
                        $accountinfo[$column] = $this->getAccountInfoCustomValue($column, $value);
                    }
                    $accountinfomap = $this->getAccountInfoColumns();
                    foreach ($accountinfo as $key => $value) {
                        unset($accountinfo[$key]);
                        $accountinfo[$accountinfomap[$key]] = $value;
                    }
                    $computers[strtoupper($table)][] = $accountinfo;
                }
            } elseif ($table != "hardware") {
                if (self::OcsTableExists($table) && $table != "glpi_plugin_ocsinventoryng_ocsservers") {
                    $request = $this->db->request([
                        'FROM'  => $table,
                        'WHERE' => ['HARDWARE_ID' => $id],
                    ]);
                    foreach ($request as $computer) {
                        if ($table == 'networks') {
                            $computers[strtoupper($table)][] = $computer;
                        } else {
                            $computers[strtoupper($table)] = $computer;
                        }
                    }
                }
            }
        }
        return $computers;
    }

    /**
     * Verify if a DB table exists
     *
     * @param $tablename string : Name of the table we want to verify.
     *
     * @return bool : true if exists, false elseway.
     **/
    public function OcsTableExists($tablename)
    {

        // Get a list of tables contained within the database.
        $request = $this->db->request([
            'SELECT' => ['table_name AS TABLE_NAME'],
            'FROM'   => 'information_schema.tables',
            'WHERE'  => [
                'table_schema' => ['=', (string) $this->dbname],
                'table_type'   => 'BASE TABLE',
                'table_name'   => ['=', (string) $tablename],
            ],
        ]);

        foreach ($request as $data) {
            if ($data['TABLE_NAME'] === $tablename) {
                return true;
            }
        }

        return false;
    }


    /*    * ******************* */
    /* PRIVATE  FUNCTIONS */
    /*    * ******************* */

    /**
     * @param     $ids
     * @param     $checksum
     * @param     $wanted
     * @param     $plugins
     * @param int $complete
     *
     * @return mixed
     */
    private function getComputerSections($ids, $checksum, $wanted, $plugins, $complete = 0)
    {
        // Put in the SQL of the OCS database: integers only
        $ids = array_map('intval', (array) $ids);
        if ($ids === []) {
            return [];
        }
        $OCS_MAP  = self::getOcsMap();
        $DATA_MAP = [];

        if ($complete > 0) {
            $DATA_MAP = $OCS_MAP;
        } else {
            foreach ($OCS_MAP as $table => $value) {
                if ($table == "dico_soft") {
                    continue;
                }
                if ($table == "hardware") {
                    $DATA_MAP[$table] = $value;
                }
                if (isset($value['checksum'])) {
                    $check = $value['checksum'];
                    if ($check & $checksum) {
                        $DATA_MAP[$table] = $value;
                    }
                } elseif (isset($value['wanted'])) {
                    $check = $value['wanted'];
                    if ($wanted & self::WANTED_ACCOUNTINFO) {
                        $DATA_MAP[$table] = $value;
                    }
                } elseif (isset($value['plugins'])) {
                    $check = $value['plugins'];
                    if (self::OcsTableExists($table) && (($check & $plugins) || $plugins == self::PLUGINS_ALL)) {
                        $DATA_MAP[$table] = $value;
                    }
                }
            }
        }

        $version = $this->getConfig("GUI_VERSION");

        foreach ($DATA_MAP as $table => $value) {
            if ($table == "dico_soft") {
                continue;
            }
            if (isset($value['checksum'])) {
                $check = $value['checksum'];
            } elseif (isset($value['wanted'])) {
                $check = $value['wanted'];
            } elseif (isset($value['plugins'])) {
                $check = $value['plugins'];
            }
            $multi = $value['multi'];

            switch ($table) {
                case "accountinfo":
                    if (($wanted & self::WANTED_ACCOUNTINFO) || $complete > 0) {
                        $request = $this->db->request([
                            'FROM'  => 'accountinfo',
                            'WHERE' => ['HARDWARE_ID' => $ids],
                        ]);
                        foreach ($request as $accountinfo) {
                            foreach ($accountinfo as $column => $value) {
                                $accountinfo[$column] = $this->getAccountInfoCustomValue($column, $value);
                            }
                            $accountinfomap = $this->getAccountInfoColumns();
                            foreach ($accountinfo as $key => $value2) {
                                unset($accountinfo[$key]);
                                $accountinfo[$accountinfomap[$key]] = $value2;
                            }
                            if ($multi) {
                                $computers[$accountinfo['HARDWARE_ID']][strtoupper($table)][] = $accountinfo;
                            } else {
                                $computers[$accountinfo['HARDWARE_ID']][strtoupper($table)] = $accountinfo;
                            }
                        }
                    }
                    break;
                case "softwares":
                    if ($version != false && $version['TVALUE'] > OcsServer::OCS2_8_VERSION_LIMIT) {
                        if (($check & $checksum) || $complete > 0) {
                            $criteria = [
                                'SELECT'    => [
                                    'software_name.NAME',
                                    'software_version.VERSION',
                                    'software_publisher.PUBLISHER',
                                    'software.COMMENTS',
                                    'software.FOLDER',
                                    'software.FILENAME',
                                    'software.FILESIZE',
                                    'software.SOURCE',
                                    'software.HARDWARE_ID',
                                    'software.GUID',
                                    'software.LANGUAGE',
                                    'software.INSTALLDATE',
                                    'software.BITSWIDTH',
                                ],
                                'FROM'      => 'software',
                                'LEFT JOIN' => [
                                    'software_name'      => [
                                        'ON' => [
                                            'software'      => 'NAME_ID',
                                            'software_name' => 'ID',
                                        ],
                                    ],
                                    'software_version'   => [
                                        'ON' => [
                                            'software'         => 'VERSION_ID',
                                            'software_version' => 'ID',
                                        ],
                                    ],
                                    'software_publisher' => [
                                        'ON' => [
                                            'software'           => 'PUBLISHER_ID',
                                            'software_publisher' => 'ID',
                                        ],
                                    ],
                                ],
                                'WHERE'     => ['software.HARDWARE_ID' => $ids],
                                'ORDER'     => 'software.id DESC',
                            ];
                            if (self::WANTED_DICO_SOFT & $wanted) {
                                $criteria['SELECT'][0]   = QueryFunction::ifnull(
                                    'dico_soft.FORMATTED',
                                    'software_name.NAME',
                                    'NAME',
                                );
                                $criteria['INNER JOIN'] = [
                                    'dico_soft' => [
                                        'ON' => [
                                            'software_name' => 'NAME',
                                            'dico_soft'     => 'EXTRACTED',
                                        ],
                                    ],
                                ];
                            }

                            foreach ($this->db->request($criteria) as $software) {
                                $computers[$software['HARDWARE_ID']]["SOFTWARES"][] = $software;
                            }
                        }
                    } else {
                        if (($check & $checksum) || $complete > 0) {
                            $criteria = [
                                'SELECT' => [
                                    'softwares.NAME',
                                    'softwares.VERSION',
                                    'softwares.PUBLISHER',
                                    'softwares.COMMENTS',
                                    'softwares.FOLDER',
                                    'softwares.FILENAME',
                                    'softwares.FILESIZE',
                                    'softwares.SOURCE',
                                    'softwares.HARDWARE_ID',
                                ],
                                'FROM'   => 'softwares',
                                'WHERE'  => ['softwares.HARDWARE_ID' => $ids],
                                'ORDER'  => 'softwares.id DESC',
                            ];
                            if ($version != false && $version['TVALUE'] > OcsServer::OCS2_VERSION_LIMIT) {
                                $criteria['SELECT'][] = 'softwares.GUID';
                                $criteria['SELECT'][] = 'softwares.LANGUAGE';
                                $criteria['SELECT'][] = 'softwares.INSTALLDATE';
                                $criteria['SELECT'][] = 'softwares.BITSWIDTH';
                            }
                            if (self::WANTED_DICO_SOFT & $wanted) {
                                $criteria['SELECT'][0]   = QueryFunction::ifnull(
                                    'dico_soft.FORMATTED',
                                    'softwares.NAME',
                                    'NAME',
                                );
                                $criteria['INNER JOIN'] = [
                                    'dico_soft' => [
                                        'ON' => [
                                            'softwares' => 'NAME',
                                            'dico_soft' => 'EXTRACTED',
                                        ],
                                    ],
                                ];
                            }

                            foreach ($this->db->request($criteria) as $software) {
                                $computers[$software['HARDWARE_ID']]["SOFTWARES"][] = $software;
                            }
                        }
                    }
                    break;
                case "registry":
                    if (($check & $checksum) || $complete > 0) {
                        $request = $this->db->request([
                            'SELECT'    => [
                                'registry.NAME AS name',
                                'registry.REGVALUE AS regvalue',
                                'registry.HARDWARE_ID AS HARDWARE_ID',
                                'regconfig.REGTREE AS regtree',
                                'regconfig.REGKEY AS regkey',
                            ],
                            'FROM'      => 'registry',
                            'LEFT JOIN' => [
                                'regconfig' => [
                                    'ON' => [
                                        'registry'  => 'NAME',
                                        'regconfig' => 'NAME',
                                    ],
                                ],
                            ],
                            'WHERE'     => ['HARDWARE_ID' => $ids],
                        ]);
                        foreach ($request as $reg) {
                            if ($multi) {
                                $computers[$reg['HARDWARE_ID']][strtoupper($table)][] = $reg;
                            } else {
                                $computers[$reg['HARDWARE_ID']][strtoupper($table)] = $reg;
                            }
                        }
                    }
                    break;
                    //plugins
                case "securitycenter":
                case "uptime":
                case "winupdatestate":
                case "teamviewer":
                case "customapp":
                case "navigatorproxysetting":
                case "winusers":
                case "osinstall":
                case "networkshare":
                case "service":
                case "runningprocess":
                case "officepack":
                case "bitlockerstatus":
                    if (self::OcsTableExists($table)) {
                        if (($check & $plugins) || $plugins == self::PLUGINS_ALL || $complete > 0) {
                            $request = $this->db->request([
                                'FROM'  => $table,
                                'WHERE' => ['HARDWARE_ID' => $ids],
                            ]);
                            foreach ($request as $plugin) {
                                if ($multi) {
                                    $computers[$plugin['HARDWARE_ID']][strtoupper($table)][$plugin['ID']] = $plugin;
                                } else {
                                    $computers[$plugin['HARDWARE_ID']][strtoupper($table)] = $plugin;
                                }
                            }
                        }
                    }
                    break;
                case "hardware":
                    $request = $this->db->request([
                        'SELECT'     => ['hardware.*', 'accountinfo.TAG'],
                        'FROM'       => 'hardware',
                        'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
                        'WHERE'      => ['hardware.ID' => $ids] + self::getArchiveCriteria($version),
                    ]);
                    foreach ($request as $meta) {
                        $computers[$meta['ID']]["META"]["ID"]       = $meta["ID"];
                        $computers[$meta['ID']]["META"]["CHECKSUM"] = $meta["CHECKSUM"];
                        $computers[$meta['ID']]["META"]["DEVICEID"] = $meta["DEVICEID"];
                        $computers[$meta['ID']]["META"]["LASTCOME"] = $meta["LASTCOME"];
                        $computers[$meta['ID']]["META"]["LASTDATE"] = $meta["LASTDATE"];
                        $computers[$meta['ID']]["META"]["NAME"]     = $meta["NAME"];
                        $computers[$meta['ID']]["META"]["TAG"]      = $meta["TAG"];
                        $computers[$meta['ID']]["META"]["USERID"]   = $meta["USERID"];
                        $computers[$meta['ID']]["META"]["UUID"]     = $meta["UUID"];
                    }

                    if (($check & $checksum) || $complete > 0) {
                        $request = $this->db->request([
                            'FROM'  => $table,
                            'WHERE' => ['ID' => $ids],
                        ]);
                        foreach ($request as $hardware) {
                            if ($multi) {
                                $computers[$hardware['ID']][strtoupper($table)][] = $hardware;
                            } else {
                                $computers[$hardware['ID']][strtoupper($table)] = $hardware;
                            }
                        }
                    }
                    break;
                default:
                    if (self::OcsTableExists($table)) {
                        if (($check & $checksum) || $complete > 0) {
                            $request = $this->db->request([
                                'FROM'  => $table,
                                'WHERE' => ['HARDWARE_ID' => $ids],
                            ]);
                            foreach ($request as $computer) {
                                if ($multi) {
                                    $computers[$computer['HARDWARE_ID']][strtoupper($table)][] = $computer;
                                } else {
                                    $computers[$computer['HARDWARE_ID']][strtoupper($table)] = $computer;
                                }
                            }
                        }
                    }
                    break;
            }
        }

        return $computers;
    }

    /**
     * @param     $ids
     * @param int $complete
     *
     * @return array
     */
    private function getSnmpSections($ids, $complete = 1)
    {
        // Put in the SQL of the OCS database: integers only
        $ids = array_map('intval', (array) $ids);
        $snmp = [];
        if ($ids === []) {
            return $snmp;
        }

        // Check for basics snmp infos
        $request = $this->db->request([
            'FROM'  => 'snmp',
            'WHERE' => ['ID' => $ids],
        ]);
        foreach ($request as $snmp_request) {
            $snmp[$snmp_request['ID']]['META'] = $snmp_request;
        }
        if ($complete) {
            // SNMP table => section of the result
            $sections = [
                'snmp_printers'        => 'PRINTER',
                'snmp_cartridges'      => 'CARTRIDGES',
                'snmp_switchs'         => 'SWITCH',
                'snmp_cards'           => 'CARDS',
                'snmp_powersupplies'   => 'POWERSUPPLIES',
                'snmp_firewalls'       => 'FIREWALLS',
                'snmp_fans'            => 'FANS',
                'snmp_trays'           => 'TRAYS',
                'snmp_memories'        => 'MEMORIES',
                'snmp_cpus'            => 'CPU',
                'snmp_networks'        => 'NETWORKS',
                'snmp_virtualmachines' => 'VIRTUALMACHINES',
                'snmp_softwares'       => 'SOFTWARES',
                'snmp_drives'          => 'COMPUTERDISKS',
            ];
            foreach ($sections as $table => $section) {
                $request = $this->db->request([
                    'FROM'  => $table,
                    'WHERE' => ['SNMP_ID' => $ids],
                ]);
                foreach ($request as $snmp_request) {
                    $snmp[$snmp_request['SNMP_ID']][$section][] = $snmp_request;
                }
            }
        }
        return $snmp;
    }

    /*    * ******************* */
    /* PUBLIC  FUNCTIONS  */
    /*    * ******************* */

    /**
     * @see OcsClient::checkConnection()
     */
    public function checkConnection(): bool
    {
        return isset($this->db) && $this->db->connected === true;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return array
     * @see OcsClient::searchComputers()
     *
     */
    public function searchComputers($field, $value)
    {

        if ($field == "id" || $field == "ID") {
            $options = [
                "FILTER" => [
                    'IDS' => [
                        $value,
                    ],
                ],
            ];
        } elseif ($field == "tag" || $field == "TAG") {
            $options = [
                "FILTER" => [
                    'TAGS' => [
                        $value,
                    ],
                ],
            ];
        } elseif ($field == "deviceid" || $field == "DEVICEID") {
            $options = [
                "FILTER" => [
                    'DEVICEIDS' => [
                        $value,
                    ],
                ],
            ];
        }

        $res = $this->getComputers($options);
        return $res;
    }

    /**
     * @param int $ssn
     * @param int $id
     *
     * @see OcsClient::updateBios()
     *
     */
    public function updateBios($ssn, $id)
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('OCS database connection is not established');
        }
        $this->db->update('bios', ['SSN' => (string) $ssn], ['HARDWARE_ID' => (int) $id]);
    }

    /**
     * @param int $tag
     * @param int $id
     *
     * @see OcsClient::updateTag()
     *
     */
    public function updateTag($tag, $id)
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('OCS database connection is not established');
        }
        $this->db->update('accountinfo', ['TAG' => (string) $tag], ['HARDWARE_ID' => (int) $id]);
    }

    /**
     * @param array $options
     *
     * @return array
     * @see OcsClient::getComputers()
     */
    public function countComputers($options)
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('OCS database connection is not established');
        }

        [, $max_records, $offset] = self::checkQueryOptions($options, 'LASTDATE');

        $version = $this->getConfig("GUI_VERSION");

        $request = $this->db->request([
            'SELECT'     => [QueryFunction::count('hardware.ID', true, 'total')],
            'FROM'       => 'hardware',
            'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
            'WHERE'      => array_merge(
                ['hardware.DEVICEID' => ['NOT LIKE', '\_%']],
                self::getArchiveCriteria($version),
                self::getComputerFilterCriteria($options),
            ),
            'LIMIT'      => $max_records,
            'START'      => $offset,
        ]);

        if (count($request)) {
            $result = $request->current();
            return $result['total'];
        }

        return ['total' => 0];
    }

    /**
     * @param array $options
     * @param int   $id
     *
     * @return array
     * @see OcsClient::getComputers()
     *
     */
    public function getComputers($options, $id = 0)
    {
        // Put in the SQL of the OCS database: integers only
        $id = (int) $id;
        if (!$this->isConnected()) {
            throw new \RuntimeException('OCS database connection is not established');
        }

        [$order, $max_records, $offset] = self::checkQueryOptions($options, 'LASTDATE');

        $version = $this->getConfig("GUI_VERSION");
        $where   = array_merge(
            self::getArchiveCriteria($version),
            self::getComputerFilterCriteria($options),
        );

        $criteria = [
            'SELECT'     => ['hardware.ID', 'hardware.LASTDATE', 'hardware.NAME'],
            'DISTINCT'   => true,
            'FROM'       => 'hardware',
            'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
        ];
        if ($id > 0) {
            $criteria['WHERE'] = array_merge(['hardware.ID' => $id], $where);
            $count_criteria    = null;
        } else {
            $where             = array_merge(['hardware.DEVICEID' => ['NOT LIKE', '\_%']], $where);
            $criteria['WHERE'] = $where;
            $criteria['ORDER'] = $order;
            $criteria['LIMIT'] = $max_records;
            $criteria['START'] = $offset;
            $count_criteria    = [
                'SELECT'     => [QueryFunction::count('hardware.ID', true, 'total')],
                'FROM'       => 'hardware',
                'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
                'WHERE'      => $where,
            ];
        }
        $request = $this->db->request($criteria);

        if ($count_criteria) {
            $count_request_result = $this->db->request($count_criteria)->current();
            $count                = $count_request_result['total'];
        } else {
            $count = count($request);
        }

        if ($count) {
            $this->getAccountInfoColumns();
            foreach ($request as $hardwareid) {
                $hardwareids[] = $hardwareid['ID'];
            }
            $res["TOTAL_COUNT"] = $count;
            if (isset($options['DISPLAY']['CHECKSUM'])) {
                $checksum = $options['DISPLAY']['CHECKSUM'];
            } else {
                $checksum = self::CHECKSUM_NONE;
            }
            if (isset($options['DISPLAY']['WANTED'])) {
                $wanted = $options['DISPLAY']['WANTED'];
            } else {
                $wanted = self::WANTED_NONE;
            }

            if (isset($options['DISPLAY']['PLUGINS'])) {
                $plugins = $options['DISPLAY']['PLUGINS'];
            } else {
                $plugins = self::PLUGINS_NONE;
            }

            $complete = 0;
            if (isset($options['COMPLETE'])) {
                $complete = $options['COMPLETE'];
            }

            $res["COMPUTERS"] = $this->getComputerSections($hardwareids, $checksum, $wanted, $plugins, $complete);
        } else {
            $res = [];
        }

        return $res;
    }

    /**
     * @param string $key
     *
     * @return bool|mixed|result
     * @see OcsClient::getConfig()
     *
     */
    public function getConfig($key)
    {
        $res = false;
        if (!empty($key)) {
            if (!$this->db || !$this->db->connected) {
                return $res;
            }
            $config = $this->db->request([
                'SELECT' => ['IVALUE', 'TVALUE'],
                'FROM'   => 'config',
                'WHERE'  => ['NAME' => ['=', (string) $key]],
            ]);
            foreach ($config as $conf) {
                $res = $conf;
            }
        }
        return $res;
    }

    /**
     * @param string $key
     * @param int    $ivalue
     * @param string $tvalue
     *
     * @see OcsClient::setConfig()
     *
     */
    public function setConfig($key, $ivalue, $tvalue)
    {
        $this->db->update(
            'config',
            [
                'IVALUE' => (int) $ivalue,
                'TVALUE' => (string) $tvalue,
            ],
            ['NAME' => ['=', (string) $key]],
        );
    }

    /**
     * @param int $checksum
     * @param int $id
     *
     * @see OcsClient::setChecksum()
     *
     */
    public function setChecksum($checksum, $id)
    {
        $this->db->update(
            'hardware',
            ['CHECKSUM' => (int) $checksum],
            ['ID' => (int) $id],
        );
    }

    /**
     * Clear some sections of the checksum of an OCS computer, in a single atomic query
     * (the other bits, set by an inventory in the meantime, are kept)
     *
     * @param int $bits CHECKSUM_* sections to clear
     * @param int $id   OCS computer ID
     *
     * @return void
     */
    public function clearChecksumBits(int $bits, int $id): void
    {
        $this->db->update(
            'hardware',
            [
                'CHECKSUM' => new QueryExpression(
                    DBmysql::quoteName('CHECKSUM') . ' & ~' . $bits,
                ),
            ],
            ['ID' => $id],
        );
    }

    /**
     * @param int $id
     *
     * @return int
     * @see OcsClient::getChecksum()
     *
     */
    public function getChecksum($id)
    {
        $res = $this->db->request([
            'SELECT' => ['CHECKSUM'],
            'FROM'   => 'hardware',
            'WHERE'  => ['ID' => (int) $id],
        ])->current();
        return isset($res["CHECKSUM"]) ? $res["CHECKSUM"] : 0;
    }

    /**
     * @param array $cfg_ocs
     * @param date  $max_date
     *
     * @return array
     * @see OcsClient::getComputersToUpdate()
     *
     */
    public function getComputersToUpdate($cfg_ocs, $max_date)
    {

        $version = $this->getConfig("GUI_VERSION");

        $where = array_merge(
            [
                [
                    'OR' => [
                        new QueryExpression(
                            '(' . DBmysql::quoteName('hardware.CHECKSUM') . ' & ' . (int) $cfg_ocs["checksum"] . ') > 0',
                        ),
                        'hardware.LASTDATE' => ['>', self::checkFilterDate($max_date)],
                    ],
                ],
            ],
            self::getArchiveCriteria($version),
            [
                // workaround to avoid duplicate when synchro occurs during an inventory
                // "after" insert in ocsweb.hardware  and "before" insert in ocsweb.deleted_equiv
                new QueryExpression(
                    'UNIX_TIMESTAMP(' . DBmysql::quoteName('LASTDATE') . ') < (UNIX_TIMESTAMP(NOW())-180)',
                ),
            ],
        );

        $tag_criteria = self::getTagLimitCriteria($cfg_ocs);
        if ($tag_criteria !== []) {
            $where[] = $tag_criteria;
        }

        $request = $this->db->request([
            'SELECT'     => ['hardware.ID'],
            'FROM'       => 'hardware',
            'INNER JOIN' => self::HARDWARE_ACCOUNTINFO_JOIN,
            'WHERE'      => $where,
            'ORDER'      => 'hardware.LASTDATE ASC',
            'LIMIT'      => (int) $cfg_ocs["cron_sync_number"],
        ]);

        $data = [];
        foreach ($request as $num) {
            $data[] = $num;
        }
        return $data;
    }

    /**
     * The tag_limit / tag_exclude configuration of the OCS server
     * (TAGs separated by "$") as criteria on accountinfo.TAG,
     * same logic as OcsServer::getTagLimit()
     *
     * @param array $cfg_ocs
     *
     * @return array
     */
    private static function getTagLimitCriteria($cfg_ocs): array
    {
        $criteria = [];
        if (!empty($cfg_ocs["tag_limit"])) {
            $tags = explode("$", trim($cfg_ocs["tag_limit"]));
            $criteria['accountinfo.TAG'] = ['IN', array_map('strval', $tags)];
        }

        if (!empty($cfg_ocs["tag_exclude"])) {
            $tags = explode("$", $cfg_ocs["tag_exclude"]);
            $criteria[] = ['accountinfo.TAG' => ['NOT IN', array_map('strval', $tags)]];
        }

        return $criteria;
    }

    /**
     * @see OcsClient::getOCSComputers()
     */
    public function getOCSComputers()
    {
        $request = $this->db->request([
            'SELECT' => ['ID', 'DEVICEID'],
            'FROM'   => 'hardware',
        ]);
        $data = [];

        foreach ($request as $computer) {
            $data[$computer['ID']] = $computer['DEVICEID'];
        }
        return $data;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function getIfOCSComputersExists($id)
    {
        $request = $this->db->request([
            'SELECT' => ['ID'],
            'FROM'   => 'hardware',
            'WHERE'  => ['ID' => (int) $id],
        ]);

        return count($request) > 0;
    }

    /**
     * @param $delay
     *
     * @return array
     * @see OcsClient::getOldAgents()
     */
    public function getOldAgents($delay)
    {
        // Put in the SQL of the OCS database: integers only
        $delay = (int) $delay;

        $request = $this->db->request([
            'SELECT' => ['id'],
            'FROM'   => 'hardware',
            'WHERE'  => [
                new QueryExpression(
                    'UNIX_TIMESTAMP(' . DBmysql::quoteName('LASTCOME') . ') <= UNIX_TIMESTAMP(NOW() - INTERVAL ' . $delay . ' DAY)',
                ),
                ['deviceid' => ['<>', '_SYSTEMGROUP_']],
                ['deviceid' => ['<>', '_DOWNLOADGROUP_']],
            ],
        ]);
        $data = [];

        foreach ($request as $num) {
            $data[] = $num;
        }
        return $data;
    }

    /**
     * @param $agents
     *
     * @return int
     */
    public function deleteOldAgents($agents)
    {

        $i = 0;

        foreach ($agents as $key => $val) {
            foreach ($val as $k => $agent) {
                // Put in the SQL of the OCS database: integers only
                $agent = (int) $agent;
                $request = $this->db->request([
                    'SELECT' => ['deviceid', 'name', 'IPADDR', 'OSNAME'],
                    'FROM'   => 'hardware',
                    'WHERE'  => ['id' => $agent],
                ]);

                foreach ($request as $num) {
                    $did = $num["deviceid"];
                    if ($did) {
                        $tables = ["accesslog",
                            "accountinfo",
                            "archive",
                            "batteries",
                            "bios",
                            "controllers",
                            "cpus",
                            "devices",
                            "download_history",
                            "download_servers",
                            "drives",
                            "groups",
                            "groups_cache",
                            "inputs",
                            "itmgmt_comments",
                            "javainfo",
                            "journallog",
                            "locks",
                            "memories",
                            "modems",
                            "monitors",
                            "networks",
                            "ports",
                            "printers",
                            "registry",
                            "saas",
                            "securitycenter",
                            "sim",
                            "uptime",
                            "officepack",
                            "winupdatestate",
                            "slots",
                            "softwares",
                            "sounds",
                            "storages",
                            "usbdevices",
                            "videos",
                            "virtualmachines",
                        ];
                        if (isset($tables) and is_array($tables)) {
                            foreach ($tables as $table) {
                                if (self::OcsTableExists($table)) {
                                    $this->db->delete($table, ['HARDWARE_ID' => $agent]);
                                }
                            }
                        }
                        $this->db->delete('download_enable', ['SERVER_ID' => $agent]);

                        $this->db->delete('hardware', ['id' => $agent]);

                        //Deleted computers tracking
                        // deviceid comes from the OCS agent
                        $this->db->insert('deleted_equiv', ['DELETED' => (string) $did, 'EQUIVALENT' => 'NULL']);
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    /**
     * @see OcsClient::getTotalDeletedComputers()
     */
    public function getTotalDeletedComputers()
    {
        $total = $this->db->request([
            'COUNT' => 'cpt',
            'FROM'  => 'deleted_equiv',
        ])->current();

        return intval($total['cpt']);
    }

    /**
     * @see OcsClient::getDeletedComputers()
     */
    public function getDeletedComputers()
    {

        //      if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
        $_SESSION["ocs_deleted_equiv"]["total"] = $this->getTotalDeletedComputers();
        //      }
        $count   = 0;
        $deleted = $this->db->request([
            'SELECT' => ['DATE', 'DELETED', 'EQUIVALENT'],
            'FROM'   => 'deleted_equiv',
            'ORDER'  => ['DATE', 'EQUIVALENT'],
            'LIMIT'  => 300,
        ]);
        foreach ($deleted as $del) {
            $computers[] = $del;
        }
        if (isset($computers)) {
            foreach ($computers as $computer) {
                $res[$computer['DELETED']] = $computer['EQUIVALENT'];
                $count++;
            }
        } else {
            $res = [];
        }
        //      $_SESSION["ocs_deleted_equiv"]["deleted"] = 0;
        if (empty($_SESSION["ocs_deleted_equiv"]["deleted"])) {
            $_SESSION["ocs_deleted_equiv"]["deleted"] = $count;
        } else {
            $_SESSION["ocs_deleted_equiv"]["deleted"] += $count;
        }
        $_SESSION["ocs_deleted_equiv"]["last_req"] = $count;
        return $res;
    }

    /**
     * @param      $deleted
     * @param null $equivclean
     *
     * @return bool
     */
    public function removeDeletedComputers($deleted, $equivclean = null)
    {
        if (is_array($deleted)) {
            if ($deleted === []) {
                return true;
            }
            $where = ['DELETED' => ['IN', array_map('strval', $deleted)]];
        } else {
            $where = ['DELETED' => ['=', (string) $deleted]];
        }
        if (empty($equivclean)) {
            $where[] = [
                'OR' => [
                    ['EQUIVALENT' => ['=', '']],
                    ['EQUIVALENT' => null],
                ],
            ];
        } else {
            $where[] = ['EQUIVALENT' => ['=', (string) $equivclean]];
        }
        return $this->db->delete('deleted_equiv', $where);
    }

    /*    * eee
     * @see OcsClient::getAccountInfoColumns()
     */

    /**
     * @param string $table
     *
     * @return mixed
     */
    public function getAccountInfoColumns($table = 'accountinfo')
    {
        if ($table == 'accountinfo') {
            // Metadata query: no DBAL equivalent, constant table name
            $query   = "SHOW COLUMNS FROM `$table`";
            $columns = $this->db->doQuery($query);
            while ($column = $this->db->fetchAssoc($columns)) {
                $res[$column['Field']] = $column['Field'];
            }
        } elseif ($table == 'hardware') {
            $res['DEVICEID'] = 'DEVICEID';
        }
        return $res;
    }

    /**
     * Criteria of the FILTER option of getSnmp()
     *
     * @param array $options
     *
     * @return array
     */
    private static function getSnmpFilterCriteria(array $options): array
    {
        if (!isset($options['FILTER']) || !is_array($options['FILTER'])) {
            return [];
        }
        $filters = $options['FILTER'];
        $where   = [];

        if (!empty($filters['IDS'])) {
            $where[] = ['snmp.ID' => ['IN', array_map('intval', (array) $filters['IDS'])]];
        }
        if (!empty($filters['EXCLUDE_IDS'])) {
            $where[] = ['snmp.ID' => ['NOT IN', array_map('intval', (array) $filters['EXCLUDE_IDS'])]];
        }
        if (!empty($filters['DEVICEIDS'])) {
            $where[] = ['snmp.SNMPDEVICEID' => ['IN', array_map('strval', (array) $filters['DEVICEIDS'])]];
        }
        if (!empty($filters['EXCLUDE_DEVICEIDS'])) {
            $where[] = ['snmp.SNMPDEVICEID' => ['NOT IN', array_map('strval', (array) $filters['EXCLUDE_DEVICEIDS'])]];
        }
        if (!empty($filters['CHECKSUM'])) {
            $where[] = new QueryExpression(
                '(' . (int) $filters['CHECKSUM'] . ' & ' . DBmysql::quoteName('snmp.CHECKSUM') . ')',
            );
        }
        if (!empty($filters['INVENTORIED_SINCE'])) {
            $where[] = ['snmp.LASTDATE' => ['>', self::checkFilterDate($filters['INVENTORIED_SINCE'])]];
        }
        if (!empty($filters['INVENTORIED_BEFORE'])) {
            $where[] = new QueryExpression(
                'UNIX_TIMESTAMP(' . DBmysql::quoteName('snmp.LASTDATE') . ') < (UNIX_TIMESTAMP(?)-180)',
                null,
                [self::checkFilterDate($filters['INVENTORIED_BEFORE'])],
            );
        }

        return $where;
    }

    /**
     * @param array $options
     *
     * @return array
     * @see OcsClient::getSnmp()
     *
     */
    public function getSnmp($options)
    {

        [$order, $max_records, $offset] = self::checkQueryOptions($options, 'LASTDATE');

        $criteria = [
            'SELECT'   => ['snmp.ID'],
            'DISTINCT' => true,
            'FROM'     => ['snmp', 'snmp_accountinfo'],
            'WHERE'    => array_merge(
                [
                    'snmp.SNMPDEVICEID' => ['NOT LIKE', '\_%'],
                    new QueryExpression(
                        DBmysql::quoteName('snmp.ID') . ' = ' . DBmysql::quoteName('snmp_accountinfo.SNMP_ID'),
                    ),
                ],
                self::getSnmpFilterCriteria($options),
            ),
        ];
        $request = $this->db->request($criteria);

        if (count($request)) {
            $count               = count($request);
            $criteria['SELECT']  = ['snmp.ID', 'snmp.NAME'];
            $criteria['ORDER']   = $order;
            $criteria['LIMIT']   = $max_records;
            $criteria['START']   = $offset;
            $request             = $this->db->request($criteria);
            $this->getAccountInfoColumns();
            foreach ($request as $snmpid) {
                $snmpids[] = $snmpid['ID'];
            }
            $res["TOTAL_COUNT"] = $count;
            $complete = 1;
            if (isset($options['COMPLETE'])) {
                $complete = $options['COMPLETE'];
            }
            $res["SNMP"] = $this->getSnmpSections($snmpids, $complete);
        } else {
            $res = [];
        }

        return $res;
    }
}
