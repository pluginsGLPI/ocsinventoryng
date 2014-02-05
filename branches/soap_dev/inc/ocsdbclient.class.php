<?php
class PluginOcsinventoryngOcsDbClient extends PluginOcsinventoryngOcsClient
{
    /**
     * @var DBmysql
     */
    private $db;
    
    public function __construct($id, $dbhost, $dbuser, $dbpassword, $dbdefault)
    {
        parent::__construct($id);
        
        $this->db = new PluginOcsinventoryngDBocs($dbhost, $dbuser, $dbpassword, $dbdefault);
    }
    
    public function getDB()
    {
        return $this->db;
    }
    
    
    
    
    /**********************/
    /* PRIVATE  FUNCTIONS */
    /**********************/
    private function parseArguments($conditions, $sort = null)
    {
        $params = "";
        if ($conditions === 1) {
            $params = " WHERE '1' ";
        } else {
            foreach ($conditions as $key => $value) {
                if (count($value) > 0) {
                    $comparateur = ($key) ? " != " : " = ";
                    foreach ($value as $id => $equals) {
                        if (!empty($params)) {
                            $params .= " AND `$id` $comparateur '" . $this->db->escape($equals) . "' ";
                        } else {
                            $params = " WHERE `$id` $comparateur '" . $this->db->escape($equals) . "' ";
                        }
                    }
                }
            }
            
        }
        if (!empty($sort))
            $params .= "ORDER BY '" . $this->db->escape($sort) . "'";
        return $params;
    }
    
    
    
    private function getComputerSections($ids, $checksum, $wanted)
    {
        
        $OCS_MAP = self::getOcsMap();
        foreach ($OCS_MAP as $table => $value) {
             if ($table == "dico_soft") {
               continue;
            }
            if (isset($value['checksum'])) {
                $check = $value['checksum'];
            } else {
                $check = $value['wanted'];
            }
            $multi = $value['multi'];
            if ($table == "accountinfo") {
                if (self::WANTED_ACCOUNTINFO & $wanted) {
                    $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
                    $request = $this->db->query($query);
                    while ($accountinfo = $this->db->fetch_assoc($request)) {
                        $computers[$accountinfo['HARDWARE_ID']][strtoupper($table)] = $accountinfo;
                    }
                }

            } elseif ($table == "softwares") {
                if ($check & $checksum) {
                     if (self::WANTED_DICO_SOFT & $wanted) {
                          $query   = "SELECT `dico_soft`.`FORMATTED` AS NAME,
                                      `softwares`.`VERSION` AS VERSION,
                                      `softwares`.`PUBLISHER` AS PUBLISHER,
                                      `softwares`.`COMMENTS` AS COMMENTS,
                                      `softwares`.`HARDWARE_ID` AS HARDWARE_ID
                                      FROM `softwares`
                                      INNER JOIN `dico_soft` ON (`softwares`.`NAME` = `dico_soft`.`EXTRACTED`)
                                      WHERE `softwares`.`HARDWARE_ID` IN (" . implode(',', $ids) . ")";
                      }
                      else{
                          $query   = "SELECT `softwares`.`NAME` AS NAME,
                                      `softwares`.`VERSION` AS VERSION,
                                      `softwares`.`PUBLISHER` AS PUBLISHER,
                                      `softwares`.`COMMENTS` AS COMMENTS,
                                      `softwares`.`HARDWARE_ID` AS HARDWARE_ID
                                      FROM `softwares`
                                      WHERE `softwares`.`HARDWARE_ID` IN (" . implode(',', $ids) . ")";
                      }
        
                      $request = $this->db->query($query);
                      while ($software = $this->db->fetch_assoc($request)) {
                              $computers[$software['HARDWARE_ID']]["SOFTWARES"][] = $software;
                      }
            }

                
            } elseif ($table == "hardware") {
                
                $query   = "SELECT `hardware`.*,`accountinfo`.`TAG` FROM `hardware`
				INNER JOIN `accountinfo` ON (`hardware`.`id` = `accountinfo`.`HARDWARE_ID`)
				WHERE `ID` IN (" . implode(',', $ids) . ")";
                $request = $this->db->query($query);
                while ($meta = $this->db->fetch_assoc($request)) {
                    $computers[$meta['ID']]["META"]["ID"]       = $meta["ID"];
                    $computers[$meta['ID']]["META"]["CHECKSUM"] = $meta["CHECKSUM"];
                    $computers[$meta['ID']]["META"]["DEVICEID"] = $meta["DEVICEID"];
                    $computers[$meta['ID']]["META"]["LASTCOME"] = $meta["LASTCOME"];
                    $computers[$meta['ID']]["META"]["LASTDATE"] = $meta["LASTDATE"];
                    $computers[$meta['ID']]["META"]["NAME"]     = $meta["NAME"];
                    $computers[$meta['ID']]["META"]["TAG"]      = $meta["TAG"];
                }
                
                if ($check & $checksum) {
                    if ($check & $checksum) {
                        $query   = "SELECT * FROM `" . $table . "` WHERE `ID` IN (" . implode(',', $ids) . ")";
                        $request = $this->db->query($query);
                        while ($hardware = $this->db->fetch_assoc($request)) {
                            if ($multi) {
                                $computers[$hardware['ID']][strtoupper($table)][] = $hardware;
                            } else {
                                $computers[$hardware['ID']][strtoupper($table)] = $hardware;
                            }
                            
                        }
                        
                        
                    }
                }
                
            } else {
                if ($check & $checksum) {
                    $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
                    $request = $this->db->query($query);    
                    while ($computer = $this->db->fetch_assoc($request)) {
                        if ($multi) {
                            $computers[$computer['HARDWARE_ID']][strtoupper($table)][] = $computer;
                        } else {
                            $computers[$computer['HARDWARE_ID']][strtoupper($table)] = $computer;
                        }
                        
                    }
                    
                    
                }
            }
        }
        
        return $computers;
        
    }
    
    
    
    /**********************/
    /* PUBLIC  FUNCTIONS  */
    /**********************/
    
    
    /**
     * @see PluginOcsinventoryngOcsClient::checkConnection()
     */
    public function checkConnection()
    {
        return $this->db->connected;
    }
    
    /**
     * @see PluginOcsinventoryngOcsClient::searchComputers()
     */
    public function searchComputers($field, $value)
    {
        
        if ($field == "id") {
            $options = array(
                "FILTER" => array(
                    'IDS' => array(
                        $value
                    )
                )
            );
        } elseif ($field == "tag") {
            $options = array(
                "FILTER" => array(
                    'TAGS' => array(
                        $value
                    )
                )
            );
        }
        $res = $this->getComputers($options);
        return $res;
    }
    
    
    
    /**
     * @see PluginOcsinventoryngOcsClient::getComputers()
     */
    
    public function getComputers($options)
    {
        
        
        if ($options['OFFSET']) {
            $offset = "OFFSET  " . $options['OFFSET'];
        } else {
            $offset = "";
        }
        if ($options['MAX_RECORDS']) {
            $max_records = "LIMIT  " . $options['MAX_RECORDS'];
        } else {
            $max_records = "";
        }
        if ($options['ORDER']) {
            $order = $options['ORDER'];
        } else {
            $order = " LASTDATE ";
        }
        
        if ($options['FILTER']) {
            $filters = $options['FILTER'];
            if (isset($filters['IDS']) and $filters['IDS']) {
                $ids       = $filters['IDS'];
                $where_ids = " AND hardware.ID IN (";
                $where_ids .= join(',', $ids);
                $where_ids .= ") ";
            } else {
                $where_ids = "";
            }
            
            if (isset($filters['EXCLUDE_IDS']) and $filters['EXCLUDE_IDS']) {
                $exclude_ids       = $filters['EXCLUDE_IDS'];
                $where_exclude_ids = " AND hardware.ID NOT IN (";
                $where_exclude_ids .= join(',', $exclude_ids);
                $where_exclude_ids .= ") ";
            } else {
                $where_exclude_ids = "";
            }
            
            if (isset($filters['TAGS']) and $filters['TAGS']) {
                $tags       = $filters['TAGS'];
                $where_tags = " AND accountinfo.TAG IN (";
                $where_tags .= join(',', $this->db->escape($tags));
                $where_tags .= ") ";
            } else {
                $where_tags = "";
            }
            
            if (isset($filters['EXCLUDE_TAGS']) and $filters['EXCLUDE_TAGS']) {
                $exclude_tags       = $filters['EXCLUDE_TAGS'];
                $where_exclude_tags = " AND accountinfo.TAG NOT IN (";
                $where_exclude_tags .= join(',', $this->db->escape($exclude_tags));
                $where_exclude_tags .= ") ";
            } else {
                $where_exclude_tags = "";
            }
            
            if (isset($filters['CHECKSUM']) and $filters['CHECKSUM']) {
                $checksum       = $filters['CHECKSUM'];
                $where_checksum = " AND ('.$checksum.' & hardware.CHECKSUM) ";
            } else {
                $where_checksum = "";
            }
            $where_condition = $where_ids . $where_exclude_ids . $where_tags . $where_exclude_tags . $where_checksum;
        }
        else{
            $where_condition="";
        }
        
       
        $query           = "SELECT DISTINCT hardware.ID FROM hardware, accountinfo
        						WHERE hardware.DEVICEID NOT LIKE '\\_%'
        						AND hardware.ID = accountinfo.HARDWARE_ID
        						$where_condition";
        $request         = $this->db->query($query);
        if ($this->db->numrows($request)) {
            
            
            $count              = $this->db->numrows($request);
            $query              = "SELECT DISTINCT hardware.ID FROM hardware, accountinfo
									WHERE hardware.DEVICEID NOT LIKE '\\_%'
									AND hardware.ID = accountinfo.HARDWARE_ID
									$where_condition
									ORDER BY $order
									$max_records  $offset";
            $request = $this->db->query($query);

            while ($hardwareid = $this->db->fetch_assoc($request)) {
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
            $res["COMPUTERS"] = $this->getComputerSections($hardwareids, $checksum, $wanted);
        }
        
        else {
            
            
            $res = array();
        }
        
        return $res;
    }
    
    
    
    
    
    
    
    
    
    /**
     * @see PluginOcsinventoryngOcsClient::getAccountInfo()
     */
    public function getAccountInfo($id)
    {
        $query       = "SELECT * FROM `accountinfo` WHERE `HARDWARE_ID` = '" . $id . "'";
        $accountinfo = $this->db->query($query);
        $res         = $this->db->fetch_assoc($accountinfo);
        return $res;
    }
    
    
    
    
    /**
     * @see PluginOcsinventoryngOcsClient::getConfig()
     */
    public function getConfig($key)
    {
        $query  = "SELECT `IVALUE`, `TVALUE` FROM `config` WHERE `NAME` = '" . $this->db->escape($key) . "'";
        $config = $this->db->query($query);
        $res    = $this->db->fetch_assoc($config);
        return $res;
    }
    
    /**
     * @see PluginOcsinventoryngOcsClient::setConfig()
     */
    public function setConfig($key, $ivalue, $tvalue)
    {
        $query = "UPDATE `config` SET `IVALUE` = '" . $ivalue . "', `TVALUE` = '" . $this->db->escape($tvalue) . "' WHERE `NAME` = '" . $this->db->escape($key) . "'";
        $this->db->query($query);
    }
    
    
    
    
    
    public function getUnique($columns, $table, $conditions, $sort)
    {
        
        
        
        
    }
    
    /**
     * @see PluginOcsinventoryngOcsClient::setChecksum()
     */
    public function setChecksum($checksum, $id)
    {
        $query    = "UPDATE `hardware` SET `CHECKSUM` = '" . $checksum . "' WHERE `ID` = '" . $id . "'";
        $checksum = $this->db->query($query);
    }
    
    /**
     * @see PluginOcsinventoryngOcsClient::getChecksum()
     */
    public function getChecksum($id)
    {
        $query    = "SELECT `CHECKSUM` FROM `hardware` WHERE `ID` = '" . $id . "'";
        $checksum = $this->db->query($query);
        $res      = $this->db->fetch_assoc($checksum);
        return $res["CHECKSUM"];
    }
    
    
    
    /**
     * @see PluginOcsinventoryngOcsClient::getDeletedComputers()
     */
    public function getDeletedComputers()
    {
        $query   = "SELECT `DATE`,`DELETED`,`EQUIVALENT` FROM `deleted_equiv` ORDER BY `DATE`,`DELETED` ";
        $deleted = $this->db->query($query);
        while ($del = $this->db->fetch_assoc($deleted)) {
            $computers[] = $del;
        }
        if (isset($computers)) {
            foreach ($computers as $computer) {
                $res[$computer['DELETED']] = $computer['EQUIVALENT'];
            }
        } else {
            $res = array();
            
        }
        return $res;
    }
    
    
    
    
    
    public function removeDeletedComputers($deleted, $equivclean = null)
    {
        $query = "DELETE FROM `deleted_equiv` WHERE `DELETED` = '" . $this->db->escape($deleted) . "'' ";
        
        if (empty($equivclean)) {
            $equiv_clean = " (`EQUIVALENT` = '" . $this->db->escape($equiv_clean) . "' OR `EQUIVALENT` IS NULL ) ";
            
        } else {
            $equiv_clean = " `EQUIVALENT` = '" . $this->db->escape($equiv_clean) . "'";
        }
        $query .= $equiv_clean;
        $delete = $this->db->query($query);
        $res    = $delete;
        return $res;
    }
    
    
    
    /**eee
     * @see PluginOcsinventoryngOcsClient::getAccountInfoColumns()
     */
    public function getAccountInfoColumns()
    {
        $query   = "SHOW COLUMNS FROM `accountinfo`";
        $columns = $this->db->query($query);
        while ($column = $this->db->fetch_assoc($columns)) {
            $res[] = $column['Field'];
        }
        return $res;
    }
    
}






