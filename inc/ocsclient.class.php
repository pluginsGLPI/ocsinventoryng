<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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

/**
 * Use an abstract class because GLPI is unable to autoload interfaces
 */
abstract class PluginOcsinventoryngOcsClient {

   const CHECKSUM_NONE                = 0x00000;
   const CHECKSUM_HARDWARE            = 0x00001;
   const CHECKSUM_BIOS                = 0x00002;
   const CHECKSUM_MEMORY_SLOTS        = 0x00004;
   const CHECKSUM_SYSTEM_SLOTS        = 0x00008;
   const CHECKSUM_REGISTRY            = 0x00010;
   const CHECKSUM_SYSTEM_CONTROLLERS  = 0x00020;
   const CHECKSUM_MONITORS            = 0x00040;
   const CHECKSUM_SYSTEM_PORTS        = 0x00080;
   const CHECKSUM_STORAGE_PERIPHERALS = 0x00100;
   const CHECKSUM_LOGICAL_DRIVES      = 0x00200;
   const CHECKSUM_INPUT_DEVICES       = 0x00400;
   const CHECKSUM_MODEMS              = 0x00800;
   const CHECKSUM_NETWORK_ADAPTERS    = 0x01000;
   const CHECKSUM_PRINTERS            = 0x02000;
   const CHECKSUM_SOUND_ADAPTERS      = 0x04000;
   const CHECKSUM_VIDEO_ADAPTERS      = 0x08000;
   const CHECKSUM_SOFTWARE            = 0x10000;
   const CHECKSUM_VIRTUAL_MACHINES    = 0x20000;
   const CHECKSUM_CPUS                = 0x40000;
   const CHECKSUM_SIM                 = 0x80000;
   const CHECKSUM_ALL                 = 0xFFFFF;
   const WANTED_NONE                  = 0x00000;
   const WANTED_ACCOUNTINFO           = 0x00001;
   const WANTED_DICO_SOFT             = 0x00002;
   const WANTED_ALL                   = 0x00003;
   const PLUGINS_NONE                 = 0x00000;
   const PLUGINS_SECURITY             = 0x00001;
   const PLUGINS_UPTIME               = 0x00002;
   const PLUGINS_OFFICE               = 0x00003;
   const PLUGINS_WUPDATE              = 0x00004;
   const PLUGINS_TEAMVIEWER           = 0x00005;
   const PLUGINS_PROXYSETTING         = 0x00006;
   const PLUGINS_WINUSERS             = 0x00007;
   const PLUGINS_OSINSTALL            = 0x00008;
   const PLUGINS_NETWORKSHARE         = 0x00009;
   const PLUGINS_SERVICE              = 0x00010;
   const PLUGINS_RUNNINGPROCESS       = 0x00011;
   const PLUGINS_ALL                  = 0x00012;
   const PLUGINS_CUSTOMAPP            = 0x00013;
   const PLUGINS_BITLOCKER            = 0x00014;

   private $id;

   /**
    * PluginOcsinventoryngOcsClient constructor.
    *
    * @param $id
    */
   public function __construct($id) {
      $this->id = $id;
   }

   /*    * ******************* */
   /* ABSTRACT FUNCTIONS */
   /*    * ******************* */

   /**
    * Return true if connection was successful, false otherwise
    *
    * @return boolean
    */
   abstract public function checkConnection();

   /**
    * Returns a list of computers for a given filter
    *
    * @param string $field The field to filter computers
    * @param mixed  $value The value to filter computers
    *
    * @return array List of computers :
    *      array (
    *         array (
    *            'ID' => ...
    *            'CHECKSUM' => ...
    *            'DEVICEID' => ...
    *            'LASTCOME' => ...
    *            'LASTDATE' => ...
    *            'NAME' => ...
    *            'TAG' => ...
    *         ),
    *         ...
    *      )
    */
   abstract public function searchComputers($field, $value);

   /**
    * Returns a list of computers
    *
    * @param array $options Possible options :
    *      array(
    *         'OFFSET' => int,
    *         'MAX_RECORDS' => int,
    *         'FILTER' => array(                  // filter the computers to return
    *            'IDS' => array(int),            // list of computer ids to select
    *            'EXCLUDE_IDS' => array(int),      // list of computer ids to exclude
    *            'TAGS' => array(string),         // list of computer tags to select
    *            'EXCLUDE_TAGS' => array(string),   // list of computer tags to exclude
    *            'CHECKSUM' => int               // filter which sections have been modified (see
    *    CHECKSUM_* constants)
    *         ),
    *         'DISPLAY' => array(      // select which sections of the computers to return
    *            'CHECKSUM' => int,   // inventory sections to return (see CHECKSUM_* constants)
    *            'WANTED' => int      // special sections to return (see WANTED_* constants)
    *         )
    *      )
    * @param int   $id hardware id
    *
    * @return array List of computers :
    *      array (
    *         'TOTAL_COUNT' => int, // the total number of computers for this query (without taking
    *    OFFSET and MAX_RECORDS into account)
    *         'COMPUTERS' => array (
    *            array (
    *               'META' => array(
    *                  'ID' => ...
    *                  'CHECKSUM' => ...
    *                  'DEVICEID' => ...
    *                  'LASTCOME' => ...
    *                  'LASTDATE' => ...
    *                  'NAME' => ...
    *                  'TAG' => ...
    *               ),
    *               'SECTION1' => array(
    *                  array(...),   // Section element 1
    *                  array(...),   // Section element 2
    *                  ...
    *               ),
    *               'SECTION2' => array(...),
    *               ...
    *            ),
    *            ...
    *         )
    *      )
    */
   abstract public function getComputers($options, $id = 0);

   /**
    * Returns a list of snmp devices
    *
    * @param array $options Possible options :
    *      array(
    *         'OFFSET' => int,
    *         'MAX_RECORDS' => int,
    *         'FILTER' => array(                  // filter the computers to return
    *            'IDS' => array(int),            // list of computer ids to select
    *            'EXCLUDE_IDS' => array(int),      // list of computer ids to exclude
    *            'TAGS' => array(string),         // list of computer tags to select
    *            'EXCLUDE_TAGS' => array(string),   // list of computer tags to exclude
    *            'CHECKSUM' => int               // filter which sections have been modified (see
    *    CHECKSUM_* constants)
    *         ),
    *         'DISPLAY' => array(      // select which sections of the computers to return
    *            'CHECKSUM' => int,   // inventory sections to return (see CHECKSUM_* constants)
    *            'WANTED' => int      // special sections to return (see WANTED_* constants)
    *         )
    *      )
    *
    * @return array List of snmp devices :
    *      array (
    *         'TOTAL_COUNT' => int, // the total number of computers for this query (without taking
    *    OFFSET and MAX_RECORDS into account)
    *         'SNMP' => array (
    *            array (
    *               'META' => array(
    *                  'ID' => ...
    *                  'CHECKSUM' => ...
    *                  'DEVICEID' => ...
    *                  'LASTCOME' => ...
    *                  'LASTDATE' => ...
    *                  'NAME' => ...
    *                  'TAG' => ...
    *               ),
    *               'SECTION1' => array(
    *                  array(...),   // Section element 1
    *                  array(...),   // Section element 2
    *                  ...
    *               ),
    *               'SECTION2' => array(...),
    *               ...
    *            ),
    *            ...
    *         )
    *      )
    */
   abstract public function getSnmp($options);

   /**
    * Returns the config for the given key
    *
    * @param string $key The name of the config item to return
    *
    * @return mixed The config value :
    *      array (
    *         'IVALUE' => integer value,
    *         'TVALUE' => text value
    *      )
    */
   abstract public function getConfig($key);

   /**
    * Sets the config for the given key
    *
    * @param string $key The name of the config item to change
    * @param int    $ivalue The integer value of the config
    * @param string $tvalue The text value of the config
    *
    * @return void
    */
   abstract public function setConfig($key, $ivalue, $tvalue);

   /**
    * Sets the checksum for the given computer
    *
    * @param int $checksum The checksum value
    * @param int $id The computer id
    *
    * @return void
    */
   abstract public function setChecksum($checksum, $id);

   /**
    * Gets the checksum for the given computer
    *
    * @param int $id The computer id
    *
    * @return int The checksum
    */
   abstract public function getChecksum($id);

   /**
    * Gets the array of computers to update with cron
    *
    * @param array $cfg_ocs Server confifguration
    * @param       $max_date
    *
    * @return array $data the computers to update
    */
   abstract public function getComputersToUpdate($cfg_ocs, $max_date);

   /**
    * Gets the array of computers for script checkocslinks.php
    *
    * @return array $data the list of computers
    */
   abstract public function getOCSComputers();

   /**
    * Get the computer that were deleted (or merged) in ocsinventory
    *
    * @return array The list of deleted computers : (DELETED contains the id or deviceid of the
    *    computer and equivalent and EQUIV contains the new id if the computer was marged) array (
    *         'DELETED' => 'EQUIV'
    *      )
    */
   /**
    * @param       $id
    * @param array $tables
    *
    * @return mixed|null
    */
   public function getOcsComputer($id, $tables = []) {

      $result = $this->getComputerRule($id, $tables);

      return $result;

   }

   abstract public function getTotalDeletedComputers();


   abstract public function getDeletedComputers();

   /**
    * @param      $deleted
    * @param null $equivclean
    *
    * @return mixed
    */
   abstract public function removeDeletedComputers($deleted, $equivclean = null);


   /**
    * Get the old agents without inventory in ocsinventory
    *
    * @return array The list of deleted computers : (DELETED contains the id or deviceid of the
    *    computer and equivalent and EQUIV contains the new id if the computer was marged) array (
    *         'DELETED' => 'EQUIV'
    *      )
    */
   abstract public function getOldAgents($nb_days);

   /**
    * Get the account info columns
    *
    * @param $table
    *
    * @return array array (
    * array (
    * '0' => 'HARDWARE_ID',
    * '1' => 'TAG',
    * '2' => ...
    * )
    */
   abstract public function getAccountInfoColumns($table);

   /**
    * Sets the ssn for the given computer
    *
    * @param int $ssn The new SSN value
    * @param int $id The computer id
    *
    * @return void
    */
   abstract public function updateBios($ssn, $id);

   /**
    * Sets the tag for the given computer
    *
    * @param int $tag The new TAG value
    * @param int $id The computer id
    *
    * @return void
    */
   abstract public function updateTag($tag, $id);

   /*    * ********************** */
   /* IMPLEMENTED FUNCTIONS */
   /*    * ********************** */

   public function getId() {
      return $this->id;
   }

   /**
    * @param       $id
    * @param array $options
    *
    * @return mixed|null
    */
   public function getComputer($id, $options = []) {

      $result = $this->getComputers($options, $id);

      if (!isset($result['TOTAL_COUNT']) || $result['TOTAL_COUNT'] < 1 || empty($result['COMPUTERS'])) {
         return null;
      }
      return current($result['COMPUTERS']);
   }

   /**
    * @param       $id
    * @param array $options
    *
    * @return mixed|null
    */
   public function getSnmpDevice($id, $options = []) {
      if (!isset($options['FILTER'])) {
         $options['FILTER'] = [];
      }

      $options['FILTER']['IDS'] = [$id];
      $result                   = $this->getSnmp($options);

      if (!isset($result['TOTAL_COUNT']) || $result['TOTAL_COUNT'] < 1 || empty($result['SNMP'])) {
         return null;
      }
      return current($result['SNMP']);
   }

   /**
    * Returns the integer config for the given key
    *
    * @param string $key The name of the config item to return
    *
    * @return integer
    * @see PluginOcsinventoryngOcsClient::getConfig()
    *
    */
   public function getIntConfig($key) {
      $config = $this->getConfig($key);
      return $config['IVALUE'] ?? 0;
   }

   /**
    * Returns the text config for the given key
    *
    * @param string $key The name of the config item to return
    *
    * @return string
    * @see PluginOcsinventoryngOcsClient::getConfig()
    *
    */
   public function getTextConfig($key) {
      $config = $this->getConfig($key);
      return $config['TVALUE'];
   }

   /**
    * @return array
    */
   public function getOcsMap() {
      $DATA_MAP = [
         'hardware'              => [
            'checksum' => self::CHECKSUM_HARDWARE,
            'multi'    => 0,
         ],
         'bios'                  => [
            'checksum' => self::CHECKSUM_BIOS,
            'multi'    => 0,
         ],
         'memories'              => [
            'checksum' => self::CHECKSUM_MEMORY_SLOTS,
            'multi'    => 1,
         ],
         'slots'                 => [
            'checksum' => self::CHECKSUM_SYSTEM_SLOTS,
            'multi'    => 1,
         ],
         'registry'              => [
            'checksum' => self::CHECKSUM_REGISTRY,
            'multi'    => 1,
         ],
         'securitycenter'        => [
            'plugins' => self::PLUGINS_SECURITY,
            'multi'   => 1,
         ],
         'uptime'                => [
            'plugins' => self::PLUGINS_UPTIME,
            'multi'   => 0,
         ],
         'winupdatestate'        => [
            'plugins' => self::PLUGINS_WUPDATE,
            'multi'   => 0,
         ],
         'osinstall'             => [
            'plugins' => self::PLUGINS_OSINSTALL,
            'multi'   => 0,
         ],
         'networkshare'          => [
            'plugins' => self::PLUGINS_NETWORKSHARE,
            'multi'   => 1,
         ],
         'runningprocess'        => [
            'plugins' => self::PLUGINS_RUNNINGPROCESS,
            'multi'   => 1,
         ],
         'service'               => [
            'plugins' => self::PLUGINS_SERVICE,
            'multi'   => 1,
         ],
         'navigatorproxysetting' => [
            'plugins' => self::PLUGINS_PROXYSETTING,
            'multi'   => 1,
         ],
         'winusers'              => [
            'plugins' => self::PLUGINS_WINUSERS,
            'multi'   => 1,
         ],
         'teamviewer'            => [
            'plugins' => self::PLUGINS_TEAMVIEWER,
            'multi'   => 0,
         ],
         'customapp'             => [
            'plugins' => self::PLUGINS_CUSTOMAPP,
            'multi'   => 1,
         ],
         'officepack'            => [
            'plugins' => self::PLUGINS_OFFICE,
            'multi'   => 1,
         ],
         'bitlockerstatus'             => [
            'plugins' => self::PLUGINS_BITLOCKER,
            'multi'  => 1,
         ],
         'controllers'           => [
            'checksum' => self::CHECKSUM_SYSTEM_CONTROLLERS,
            'multi'    => 1,
         ],
         'monitors'              => [
            'checksum' => self::CHECKSUM_MONITORS,
            'multi'    => 1,
         ],
         'ports'                 => [
            'checksum' => self::CHECKSUM_SYSTEM_PORTS,
            'multi'    => 1,
         ],
         'storages'              => [
            'checksum' => self::CHECKSUM_STORAGE_PERIPHERALS,
            'multi'    => 1,
         ],
         'drives'                => [
            'checksum' => self::CHECKSUM_LOGICAL_DRIVES,
            'multi'    => 1,
         ],
         'inputs'                => [
            'checksum' => self::CHECKSUM_INPUT_DEVICES,
            'multi'    => 1,
         ],
         'modems'                => [
            'checksum' => self::CHECKSUM_MODEMS,
            'multi'    => 1,
         ],
         'networks'              => [
            'checksum' => self::CHECKSUM_NETWORK_ADAPTERS,
            'multi'    => 1,
         ],
         'printers'              => [
            'checksum' => self::CHECKSUM_PRINTERS,
            'multi'    => 1,
         ],
         'sounds'                => [
            'checksum' => self::CHECKSUM_SOUND_ADAPTERS,
            'multi'    => 1,
         ],
         'videos'                => [
            'checksum' => self::CHECKSUM_VIDEO_ADAPTERS,
            'multi'    => 1,
         ],
         'softwares'             => [
            'checksum' => self::CHECKSUM_SOFTWARE,
            'multi'    => 1,
         ],
         'virtualmachines'       => [
            'checksum' => self::CHECKSUM_VIRTUAL_MACHINES,
            'multi'    => 1,
         ],
         'cpus'                  => [
            'checksum' => self::CHECKSUM_CPUS,
            'multi'    => 1,
         ],
         'sim'                   => [
            'checksum' => self::CHECKSUM_SIM,
            'multi'    => 1,
         ],
         'accountinfo'           => [
            'wanted' => self::WANTED_ACCOUNTINFO,
            'multi'  => 1,
         ],
         'dico_soft'             => [
            'wanted' => self::WANTED_DICO_SOFT,
            'multi'  => 0,
         ],
      ];

      return $DATA_MAP;
   }

   /**
    * @param $tables
    *
    * @return int
    */
   public function getChecksumForTables($tables) {
      $ocsMap   = $this->getOcsMap();
      $checksum = self::CHECKSUM_NONE;

      foreach ($tables as $tableName) {
         if (isset($ocsMap[$tableName]['checksum'])) {
            $checksum |= $ocsMap[$tableName]['checksum'];
         }
      }

      return $checksum;
   }

   /**
    * @param $tables
    *
    * @return int
    */
   public function getWantedForTables($tables) {
      $ocsMap = $this->getOcsMap();
      $wanted = self::WANTED_NONE;

      foreach ($tables as $tableName) {
         if (isset($ocsMap[$tableName]['wanted'])) {
            $wanted |= $ocsMap[$tableName]['wanted'];
         }
      }

      return $wanted;
   }

   /**
    * @param $tables
    *
    * @return int
    */
   public function getPluginsForTables($tables) {
      $ocsMap  = $this->getOcsMap();
      $plugins = self::PLUGINS_NONE;

      foreach ($tables as $tableName) {
         if (isset($ocsMap[$tableName]['plugins'])) {
            $plugins |= $ocsMap[$tableName]['plugins'];
         }
      }

      return $plugins;
   }

   /**
    * @return string
    */
   public function getConnectionType() {
      return get_class($this);
   }

}
