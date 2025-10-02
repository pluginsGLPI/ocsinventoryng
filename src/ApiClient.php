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
use GLPIKey;
use Toolbox;

/**
 * Class ApiClient
 */
class ApiClient extends OcsClient {
   /**
    * @var apiClient
    */
   private $apiClient;

   /**
    * ApiClient constructor.
    *
    * @param $id
    * @param $url
    */
   public function __construct($id, $url) {
      parent::__construct($id);

      $this->url = $url;
   }

   /**
    * @see OcsClient::checkConnection()
    */
   public function checkConnection() {
      //      return !is_soap_fault($this->apiClient->ocs_config_V2('LOGLEVEL'));

      $url     = $this->url . "/ocsapi/v1/computers/listID";
      $options = array("url"      => $url,
                       "download" => false,
      );
      $xml     = [];

      $contents = self::cURLData($options);

      if ($contents != NULL) {
         $xml = json_decode($contents);
      }

      return (is_array($xml) && count($xml) > 0) ? true : false;
   }


   /**
    * @param $serverId
    *
    * @return bool
    */
   public function checkVersion($serverId) {
      //TODO API
      Toolbox::logDebug("cannot get version");
      return true;
   }


   /**
    * @return bool
    */
   public function checkTraceDeleted() {
      //TODO API
      Toolbox::logDebug("cannot get Trace Deleted");
      return true;
   }

   /**
    * @param $options
    *
    * @return mixed|string
    */
   static function cURLData($options) {
      global $CFG_GLPI;

      if (!function_exists('curl_init')) {
         return __('Curl PHP package not installed', 'ocsinventoryng') . "\n";
      }
      $data        = '';
      $timeout     = 10;
      $proxy_host  = $CFG_GLPI["proxy_name"] . ":" . $CFG_GLPI["proxy_port"]; // host:port
      $proxy_ident = $CFG_GLPI["proxy_user"] . ":" .(new GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"]); // username:password


      $post = "";
      //      Do we have post field to send?
      if (isset($options["post"])
          && is_array($options["post"])
          && count($options["post"]) > 0) {
         $post = "?";
         foreach ($options['post'] as $key => $value) {
            if (!is_array($value)) {
               $post .= $key . '=' . $value . '&';
            }
         }
         $post = substr($post, 0, -1);
      }

      $url = $options["url"] . $post;
      $ch  = curl_init();

      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

      if (preg_match('`^https://`i', $options["url"])) {
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
      curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
      curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile"); # SAME cookiefile

      if (!$options["download"]) {
         //curl_setopt($ch, CURLOPT_HEADER, 1);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      }

      // Activation de l'utilisation d'un serveur proxy
      if (!empty($CFG_GLPI["proxy_name"])) {
         //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);

         // Définition de l'adresse du proxy
         curl_setopt($ch, CURLOPT_PROXY, $proxy_host);

         // Définition des identifiants si le proxy requiert une identification
         if ($proxy_ident) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_ident);
         }
      }
      if ($options["download"]) {
         $fp = fopen($options["file"], "w");
         curl_setopt($ch, CURLOPT_FILE, $fp);
         curl_exec($ch);
      } else {
         $data = curl_exec($ch);
      }

      if (!$options["download"] && !$data) {
         $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch); // make sure we closeany current curl sessions
         //die($http_code.' Unable to connect to server. Please come back later.');
      } else {
         curl_close($ch);
      }

      //      if ($options["download"]) {
      //         fclose($fp);
      //      }
      if (!$options["download"] && $data) {
         return $data;
      }
   }


   /**
    * @param       $ocsid
    * @param array $tables
    * @param int   $import
    *
    * @return array
    */
   public function getComputerInfos($ocsid, $tables = array(), $import = 0) {

      //      Toolbox::logDebug($tables);
      $computer = array();

      $url     = $this->url . "/ocsapi/v1/computer/" . $ocsid . "/accountinfo";
      $options = array("url"      => $url,
                       "download" => false,
      );

      $datas    = self::cURLData($options);
      $metadata = json_decode($datas, true);

      $accountinfos = $metadata[$ocsid]["accountinfo"][0];

      foreach ($accountinfos as $name => $value) {
         $computer["ACCOUNTINFO"][$name] = $value;
      }

      $hardware = $metadata[$ocsid]["hardware"];

      $metadatas = ["ID", "CHECKSUM", "DEVICEID", "LASTCOME", "LASTDATE", "NAME", "TAG", "USERID", "UUID"];
      foreach ($hardware as $name => $value) {
         if (in_array($name, $metadatas)) {
            $computer["META"][$name] = $value;
         }
         $computer["HARDWARE"][$name] = $value;
      }

      //      $ocsMap      = $this->getOcsMap();
      if ($import == 0) {
         $DATA_MAP = array(
            'bios'     => array(
               'checksum' => OcsClient::CHECKSUM_BIOS,
               'multi'    => 0,
            ),
            'networks' => array(
               'checksum' => OcsClient::CHECKSUM_NETWORK_ADAPTERS,
               'multi'    => 1,
            ),
         );
      } else if ($import == 1) {
         $DATA_MAP = array(
            'bios'                  => array(
               'checksum' => OcsClient::CHECKSUM_BIOS,
               'multi'    => 0,
            ),
            'memories'              => array(
               'checksum' => OcsClient::CHECKSUM_MEMORY_SLOTS,
               'multi'    => 1,
            ),
            'slots'                 => array(
               'checksum' => OcsClient::CHECKSUM_SYSTEM_SLOTS,
               'multi'    => 1,
            ),
            'registry'              => array(
               'checksum' => OcsClient::CHECKSUM_REGISTRY,
               'multi'    => 1,
            ),
            'controllers'           => array(
               'checksum' => OcsClient::CHECKSUM_SYSTEM_CONTROLLERS,
               'multi'    => 1,
            ),
            'monitors'              => array(
               'checksum' => OcsClient::CHECKSUM_MONITORS,
               'multi'    => 1,
            ),
            'ports'                 => array(
               'checksum' => OcsClient::CHECKSUM_SYSTEM_PORTS,
               'multi'    => 1,
            ),
            'storages'              => array(
               'checksum' => OcsClient::CHECKSUM_STORAGE_PERIPHERALS,
               'multi'    => 1,
            ),
            'drives'                => array(
               'checksum' => OcsClient::CHECKSUM_LOGICAL_DRIVES,
               'multi'    => 1,
            ),
            'inputs'                => array(
               'checksum' => OcsClient::CHECKSUM_INPUT_DEVICES,
               'multi'    => 1,
            ),
            'modems'                => array(
               'checksum' => OcsClient::CHECKSUM_MODEMS,
               'multi'    => 1,
            ),
            'networks'              => array(
               'checksum' => OcsClient::CHECKSUM_NETWORK_ADAPTERS,
               'multi'    => 1,
            ),
            'printers'              => array(
               'checksum' => OcsClient::CHECKSUM_PRINTERS,
               'multi'    => 1,
            ),
            'sounds'                => array(
               'checksum' => OcsClient::CHECKSUM_SOUND_ADAPTERS,
               'multi'    => 1,
            ),
            'videos'                => array(
               'checksum' => OcsClient::CHECKSUM_VIDEO_ADAPTERS,
               'multi'    => 1,
            ),
            'softwares'             => array(
               'checksum' => OcsClient::CHECKSUM_SOFTWARE,
               'multi'    => 1,
            ),
            'virtualmachines'       => array(
               'checksum' => OcsClient::CHECKSUM_VIRTUAL_MACHINES,
               'multi'    => 1,
            ),
            'cpus'                  => array(
               'checksum' => OcsClient::CHECKSUM_CPUS,
               'multi'    => 1,
            ),
            'sim'                   => array(
               'checksum' => OcsClient::CHECKSUM_SIM,
               'multi'    => 1,
            ),
            //TODO API
            //            'accountinfo'           => array(
            //               'wanted' => self::WANTED_ACCOUNTINFO,
            //               'multi'  => 1,
            //            ),
            //            'dico_soft'             => array(
            //               'wanted' => self::WANTED_DICO_SOFT,
            //               'multi'  => 0,
            //            ),
            //PLUGINS
            'securitycenter'        => array(
               'plugins' => OcsClient::PLUGINS_SECURITY,
               'multi'   => 1,
            ),
            'uptime'                => array(
               'plugins' => OcsClient::PLUGINS_UPTIME,
               'multi'   => 0,
            ),
            'winupdatestate'        => array(
               'plugins' => OcsClient::PLUGINS_WUPDATE,
               'multi'   => 0,
            ),
            'osinstall'             => array(
               'plugins' => OcsClient::PLUGINS_OSINSTALL,
               'multi'   => 0,
            ),
            'networkshare'          => array(
               'plugins' => OcsClient::PLUGINS_NETWORKSHARE,
               'multi'   => 1,
            ),
            'runningprocess'        => array(
               'plugins' => OcsClient::PLUGINS_RUNNINGPROCESS,
               'multi'   => 1,
            ),
            'service'               => array(
               'plugins' => OcsClient::PLUGINS_SERVICE,
               'multi'   => 1,
            ),
            'navigatorproxysetting' => array(
               'plugins' => OcsClient::PLUGINS_PROXYSETTING,
               'multi'   => 1,
            ),
            'winusers'              => array(
               'plugins' => OcsClient::PLUGINS_WINUSERS,
               'multi'   => 1,
            ),
            'teamviewer'            => array(
               'plugins' => OcsClient::PLUGINS_TEAMVIEWER,
               'multi'   => 0,
            ),
            'officepack'            => array(
               'plugins' => OcsClient::PLUGINS_OFFICE,
               'multi'   => 1,
            ),
         );
      }
      foreach ($DATA_MAP as $section => $infos) {
         $url      = $this->url . "/ocsapi/v1/computer/" . $ocsid . "/$section";
         $options  = array("url"      => $url,
                           "download" => false,
         );
         $datas    = self::cURLData($options);
         $metadata = json_decode($datas, true);

         if ($infos['multi'] == 1) {
            if (isset($metadata[$ocsid][$section])) {
               $datas_section = $metadata[$ocsid][$section];
            }
         } else if ($infos['multi'] == 0) {
            if (isset($metadata[$ocsid][$section][0])) {
               $datas_section = $metadata[$ocsid][$section][0];
            }
         }
         foreach ($datas_section as $name => $value) {
            $computer[strtoupper($section)][$name] = $value;
         }
      }

      //      $url     = $this->url . "/ocsapi/v1/computer/" . $ocsid."/networks";
      //      $options = array("url"      => $url,
      //                       "download" => false,
      //      );
      //      $datas = self::cURLData($options);
      //      $metadata = json_decode($datas, true);
      //      $networks = $metadata[$ocsid]["networks"];
      //
      //      foreach ($networks as $name => $value) {
      //         $computer["NETWORKS"][$name] = $value;
      //      }

      //
      //      $query = "SELECT `hardware`.*,`accountinfo`.`TAG` FROM `hardware`
      //            INNER JOIN `accountinfo` ON (`hardware`.`id` = `accountinfo`.`HARDWARE_ID`)
      //            WHERE `hardware`.`ID` = $id";
      //
      //      $request = $this->db->doQuery($query);
      //      while ($meta = $this->db->fetchAssoc($request)) {

      /*$query   = "SELECT * FROM `hardware` WHERE `ID` = $id";
      $request = $this->db->doQuery($query);
      while ($hardware = $this->db->fetchAssoc($request)) {

         $computers[strtoupper('hardware')] = $hardware;

      }


      foreach ($tables as $table) {

         if ($table == "accountinfo") {
            $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` = $id";
            $request = $this->db->doQuery($query);
            while ($accountinfo = $this->db->fetchAssoc($request)) {
               foreach ($accountinfo as $column => $value) {
                  if (preg_match('/fields_\d+/', $column, $matches)) {
                     $colnumb = explode("fields_", $matches['0']);

                     if (self::OcsTableExists("accountinfo_config")) {
                        $col            = $colnumb['1'];
                        $query          = "SELECT ID,NAME FROM accountinfo_config WHERE ID = '" . $col . "'";
                        $requestcolname = $this->db->doQuery($query);
                        $colname        = $this->db->fetchAssoc($requestcolname);
                        if ($colname['NAME'] != "") {
                           if (!is_null($value)) {
                              $name         = "ACCOUNT_VALUE_" . $colname['NAME'] . "_" . $value;
                              $query        = "SELECT TVALUE,NAME FROM config WHERE NAME = '" . $name . "'";
                              $requestvalue = $this->db->doQuery($query);
                              $custom_value = $this->db->fetchAssoc($requestvalue);
                              if (isset($custom_value['TVALUE'])) {
                                 $accountinfo[$column] = $custom_value['TVALUE'];
                              }
                           }
                        }
                     }
                  }
               }
               $accountinfomap = $this->getAccountInfoColumns();
               foreach ($accountinfo as $key => $value) {
                  unset($accountinfo[$key]);
                  $accountinfo[$accountinfomap[$key]] = $value;
               }
               $computers[strtoupper($table)][] = $accountinfo;

            }
         } else if ($table != "hardware") {
            if (self::OcsTableExists($table) && $table != "glpi_plugin_ocsinventoryng_ocsservers") {
               $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` = $id";
               $request = $this->db->doQuery($query);
               while ($computer = $this->db->fetchAssoc($request)) {
                  if ($table == 'networks') {
                     $computers[strtoupper($table)][] = $computer;
                  } else {
                     $computers[strtoupper($table)] = $computer;
                  }
               }
            }
         }
      }*/
      return $computer;
   }

   /**
    * @param string $field
    * @param mixed  $value
    *
    * @return array
    */
      public function searchComputers($field, $value) {
         $xml = $this->callApi('search_computers_V1', array($field, $value));

         $computerObjs = simplexml_load_string($xml);

         $computers = array();
         if (count($computerObjs) > 0) {
            foreach ($computerObjs as $obj) {
               $computers [] = array(
                  'ID'       => (int)$obj->DATABASEID,
                  'CHECKSUM' => (int)$obj->CHECKSUM,
                  'DEVICEID' => (string)$obj->DEVICEID,
                  'LASTCOME' => (string)$obj->LASTCOME,
                  'LASTDATE' => (string)$obj->LASTDATE,
                  'NAME'     => (string)$obj->NAME,
                  'TAG'      => (string)$obj->TAG
               );
            }
         }
         return $computers;
      }

   /**
    * @param $id
    *
    * @return bool
    */
   public function getIfOCSComputersExists($id) {

      $metadata = [];
      $url      = $this->url . "/ocsapi/v1/computers/search?id=" . $id;
      $options  = array("url"      => $url,
                        "download" => false,
      );
      $datas    = self::cURLData($options);
      $metadata = json_decode($datas, true);

      if (count($metadata) > 0) {
         return true;
      }
      return false;
   }

   /**
    * @param       $id
    * @param array $options
    *
    * @return array|mixed|null
    */
   public function getComputer($id, $options = array()) {

      $computer = $this->getComputerInfos($id, array(), 1);
      return $computer;

   }

   /**
    * @see OcsClient::getComputers()
    *
    * @param array $options
    *
    * @param int   $id
    *
    * @return array
    */
   public function getComputers($options, $id = 0) {

      //      Toolbox::logDebug($options);
      $offset     = $originalOffset = isset($options['OFFSET']) ? (int)$options['OFFSET'] : 0;
      $maxRecords = isset($options['MAX_RECORDS']) ? (int)$options['MAX_RECORDS'] : null;
      $checksum   = isset($options['CHECKSUM']) ? (int)$options['CHECKSUM'] : 131071;
      $wanted     = isset($options['WANTED']) ? (int)$options['WANTED'] : 131071;
      $asking_for = isset($options['"ASKING_FOR"']) ? $options['ASKING_FOR'] : "INVENTORY";
      $engine     = isset($options['"ENGINE"']) ? $options['ENGINE'] : "FIRST";

      $originalEnd = isset($options['MAX_RECORDS']) ? $originalOffset + $maxRecords : null;
      $ocsMap      = $this->getOcsMap();

//      Toolbox::logDebug($options);

      $computers = array();

      //      $computerObjs = [];
      //
      //      $url      = $this->url . "/ocsapi/v1/computers/search";
      //      $params   = array("url"      => $url,
      //                        "post"     => $options,
      //                        "download" => false,
      //      );
      //      $contents = self::cURLData($params);
      //      //
      //      if ($contents != NULL) {
      //         $computerObjs = json_decode($contents, true);
      //      }
      //
      //      $list  = [];
      //      $count = 0;
      $computerObjs = self::listComputers($options);

      if (is_array($computerObjs)) {
         //         foreach ($computerObjs as $obj => $ocsid) {
         //            $list[] = $ocsid[0];
         //         }


         //         $xml = $this->callApi('get_computers_V1', new OcsApiRequest($options));

         //         $computerObjs = array();
         //         $computerObjs = simplexml_load_string($xml);

         $count = count($computerObjs);
         //      $count = 0;
         //      do {
         //         $options['ENGINE']     = $engine;
         //         $options['ASKING_FOR'] = $asking_for;
         //         $options['CHECKSUM']   = $checksum;
         //         $options['OFFSET']     = $offset;
         //         if (!is_null($maxRecords)) {
         //            $options['MAX_RECORDS'] = $maxRecords;
         //         }


         if ($count > 0) {

            foreach ($computerObjs as $ocsid) {

               $computer = $this->getComputerInfos($ocsid, array(), 0);


               //               $computer[]= json_decode($datas, true);

               //               foreach ($metadata as $k => $sections) {
               //                  if (is_array($sections)){
               //                     foreach ($sections as $key => $val) {
               //                        $computer[$sections] = $val;
               //                     }
               //                  }
               //               }

               //               Toolbox::logDebug($computer);
               //               die();

               //               $accountinfos = $metadata[$id]["accountinfo"][0];
               //
               //               foreach ($accountinfos as $name => $value) {
               //                  $computer["META"][$name] = $value;
               //               }
               //
               //               $hardware = $metadata[$id]["hardware"];
               //
               //               foreach ($hardware as $name => $value) {
               //                  $computer["META"][$name] = $value;
               //               }


               //               foreach ($obj->children() as $sectionName => $sectionObj) {
               //                  $section          = array();
               //                  $special_sections = array('ACCOUNTINFO', 'DICO_SOFT');
               //                  foreach ($sectionObj as $key => $val) {
               //                     if (in_array($sectionName, $special_sections)) {
               //                        $section[(string)$val->attributes()->Name] = (string)$val;
               //                     } else {
               //                        $section[$key] = (string)$val;
               //                     }
               //                  }
               //                  if (!isset($computer[$sectionName])) {
               //                     $computer[$sectionName] = array();
               //                  }
               //
               //                  $lowerSectionName = strtolower($sectionName);
               //                  if (isset($ocsMap[$lowerSectionName]) and !$ocsMap[$lowerSectionName]['multi']) {
               //                     $computer[$sectionName] = $section;
               //                  } else {
               //                     $computer[$sectionName] [] = $section;
               //                  }
               //               }

               //            if (array_key_exists('ACCOUNTINFO', $computer)) {
               //               foreach ($computer['ACCOUNTINFO'] as $number => $accountinfo) {
               //                  $computer['ACCOUNTINFO'][$accountinfo]['HARDWARE_ID'] = $computer['META']['ID'];
               //               }
               //            }

               $computers[$computer['META']['ID']] = $computer;
            }
         }
      }
      //         $totalCount = (int)$count;
      //         //         $maxRecords = (int)$computerObjs['MAX_RECORDS'];
      //         $offset += $maxRecords;
      //
      //         // We can't load more records than there is in ocs
      //         $end = (is_null($originalEnd) or $originalEnd > $totalCount) ? $totalCount : $originalEnd;
      //
      //         if ($offset + $maxRecords > $end) {
      //            $maxRecords = $end - $offset;
      //         }
      //      } while ($offset + $maxRecords < $end);

      //      toolbox::logdebug($computers);
      return array(
         'TOTAL_COUNT' => $count,
         'COMPUTERS'   => $computers
      );
   }

   /**
    * @param     $ids
    * @param int $checksum
    * @param int $wanted
    *
    * @return array
    */
   //   public function getComputerSections($ids, $checksum = self::CHECKSUM_ALL, $wanted = self::WANTED_ALL) {
   //      $xml = $this->callApi('get_computer_sections_V1', array($ids, $checksum, $wanted));
   //
   //      $computerObjs = simplexml_load_string($xml);
   //
   //      $computers = array();
   //      foreach ($computerObjs as $obj) {
   //         $id       = (int)$obj['ID'];
   //         $computer = array();
   //
   //         foreach ($obj as $sectionName => $sectionObj) {
   //            $computer[$sectionName] = array();
   //            foreach ($sectionObj as $key => $val) {
   //               $computer[$sectionName][$key] = (string)$val;
   //            }
   //         }
   //
   //         $computers[$id] = $computer;
   //      }
   //
   //      return $computers;
   //   }

   /**
    * @param $id
    */
   public function getAccountInfo($id) {
   }


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
    *            'CHECKSUM' => int               // filter which sections have been modified (see CHECKSUM_* constants)
    *         ),
    *         'DISPLAY' => array(      // select which sections of the computers to return
    *            'CHECKSUM' => int,   // inventory sections to return (see CHECKSUM_* constants)
    *            'WANTED' => int      // special sections to return (see WANTED_* constants)
    *         )
    *      )
    *
    * @return void List of snmp devices :
    *      array (
    *         'TOTAL_COUNT' => int, // the total number of computers for this query (without taking OFFSET and
    *    MAX_RECORDS into account)
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
   public function getSnmp($options) {
   }

   /**
    * @see OcsClient::getConfig()
    *
    * @param string $key
    *
    * @return array|bool|mixed
    */
   public function getConfig($key) {

      //      Toolbox::logDebug($key);


      //      if (!is_soap_fault($xml)) {
      //         $configObj = simplexml_load_string($xml);
      //         $config    = array(
      //            'IVALUE' => (int)$configObj->IVALUE,
      //            'TVALUE' => (string)$configObj->TVALUE
      //         );
      //         return $xml;
      //      }
      Toolbox::logDebug("cannot get Config");
      return true;
   }

   /**
    * @see OcsClient::setConfig()
    *
    * @param string $key
    * @param int    $ivalue
    * @param string $tvalue
    */
   public function setConfig($key, $ivalue, $tvalue) {
      $this->callApi('ocs_config_V2', array(
         $key,
         $ivalue,
         $tvalue
      ));
   }

   /**
    * Gets the array of computers to update with cron
    *
    * @param array $cfg_ocs Server confifguration
    * @param       $max_date
    *
    * @return void $data the computers to update
    */
   public function getComputersToUpdate($cfg_ocs, $max_date) {
   }

   /**
    * Gets the array of computers for script checkocslinks.php
    *
    * @return void $data the list of computers
    */
   public function getOCSComputers() {
   }

   /**
    *
    */
   public function getDeletedComputers() {
      $deletedObjs = array();
      //$xml = $this->callApi('get_deleted_computers_V1', new OcsApiRequest(array()));
      //$deletedObjs = simplexml_load_string($xml);
      $res = array();

      /* if (is_array($deletedObjs) && count($deletedObjs) > 0) {
          foreach ($deletedObjs as $obj) {
             $res[(string) $obj['DELETED']] = (string) $obj['EQUIVALENT'];
          }
       }
       if($res != array()){
          $_SESSION["ocs_deleted_equiv"]['computers_to_del']=true;
       }
       return $res;*/
      return array();
   }

   /**
    * @param      $deleted
    * @param null $equiv
    *
    * @return mixed|void
    */
   public function removeDeletedComputers($deleted, $equiv = null) {
      $count = 0;
      if (is_array($deleted)) {
         foreach ($deleted as $del) {
            $this->callApi('remove_deleted_computer_V1', array($del));
            $count++;
         }
      } else {
         if ($equiv) {
            $this->callApi('remove_deleted_computer_V1', array($deleted, $equiv));
            $count++;
         } else {
            $this->callApi('remove_deleted_computer_V1', array($deleted));
            $count++;
         }
      }
      $_SESSION["ocs_deleted_equiv"]['computers_deleted'] += $count;
   }


   /**
    * Get the old agents without inventory in ocsinventory
    *
    * @return void The list of deleted computers : (DELETED contains the id or deviceid of the computer and equivalent
    *    and EQUIV contains the new id if the computer was marged) array (
    *         'DELETED' => 'EQUIV'
    *      )
    */
   public function getOldAgents($delay) {
   }

   /**
    * @param $columns
    * @param $table
    * @param $conditions
    * @param $sort
    */
   public function getUnique($columns, $table, $conditions, $sort) {
   }

   /**
    * @param int $checksum
    * @param int $id
    */
   public function setChecksum($checksum, $id) {
      //TODO API
      //      $this->callApi('reset_checksum_V1', array($checksum, $id));
      Toolbox::logDebug("cannot set checksum");
   }

   /**
    * @param int $id
    *
    * @return int
    */
   public function getChecksum($id) {

      $checksum = 0;

      $url     = $this->url . "/ocsapi/v1/computer/" . $id . "/hardaware";
      $options = array("url"      => $url,
                       "download" => false,
      );

      $datas    = self::cURLData($options);
      $metadata = json_decode($datas, true);

      if (isset($metadata[$id]["hardware"]["CHECKSUM"])) {
         return $metadata[$id]["hardware"]["CHECKSUM"];
      }

      return false;

   }


   /**
    * @see  OcsClient::getAccountInfoColumns()
    *
    * @param $table
    *
    * @return array
    */
   public function getAccountInfoColumns($table) {

      //TODO API
      $xml = [];
      $res = array(
         'HARDWARE_ID' => 'HARDWARE_ID',
         'TAG'         => 'TAG'
      );
      $res = array_merge($res, (array)$xml);
      return $res;

   }



   //****************************
   //TO IMPLEMENT
   //****************************


   /**
    * @see  OcsClient::updateBios()
    *
    * @param int $ssn
    * @param int $id
    */
   public function updateBios($ssn, $id) {


   }

   /**
    * @see OcsClient::updateTag()
    *
    * @param int $tag
    * @param int $id
    */
   public function updateTag($tag, $id) {
   }


   /**
    * @param     $options
    * @param int $id
    *
    * @return array
    */
   public function countComputers($options, $id = 0) {

      $exclude_ids = [];
      if (is_array($options) && count($options) > 0) {
         if (isset($options['FILTER']['EXCLUDE_IDS'])
             && is_array($options['FILTER']['EXCLUDE_IDS'])) {
            foreach ($options['FILTER']['EXCLUDE_IDS'] as $key => $value) {
               $exclude_ids[] = $value;
            }
         }
      }
      $url          = $this->url . "/ocsapi/v1/computers/listID";
      $params       = array("url"      => $url,
                            "download" => false,
      );
      $computerObjs = [];

      $contents = self::cURLData($params);
      if ($contents != NULL) {
         $computerObjs = json_decode($contents, true);
      }
      $listID = [];
      foreach ($computerObjs as $key => $id) {
         foreach ($id as $k => $v) {
            if (!in_array($v, $exclude_ids)) {
               $listID[] = $v;
            }
         }
      }
      return $listID;
   }

   /**
    * @param     $options
    * @param int $id
    *
    * @return array
    */
   public function listComputers($options, $id = 0) {

      $exclude_ids = [];
      $post        = [];
      if (is_array($options) && count($options) > 0) {
         if (isset($options['FILTER']['EXCLUDE_IDS'])
             && is_array($options['FILTER']['EXCLUDE_IDS'])) {
            foreach ($options['FILTER']['EXCLUDE_IDS'] as $key => $value) {
               $exclude_ids[] = $value;
            }
         }
         $post["type"]  = 0;
         $post["start"] = 0;
         $post["limit"] = 5;

         if (isset($options['MAX_RECORDS'])) {
            $post["limit"] = $options['MAX_RECORDS'];
         }
         if (isset($options['OFFSET'])) {
            $post["start"] = $options['OFFSET'];
         }
      }

      $url          = $this->url . "/ocsapi/v1/computers/search";
      $params       = array("url"      => $url,
                            "post"     => $post,
                            "download" => false,
      );
      $computerObjs = [];

      $contents = self::cURLData($params);
      if ($contents != NULL) {
         $computerObjs = json_decode($contents, true);
      }

      $listID = [];
      foreach ($computerObjs as $key => $id) {
         foreach ($id as $k => $v) {
//            if (!in_array($v, $exclude_ids)) {
               $listID[] = $v;
//            }
         }
      }
      return $listID;
   }

   // /**
   // * @see OcsClient::getComputers()
   // */
   // public function getComputers($options = array()) {
   // // TODO don't use simplexml
   // // TODO paginate
   // // Get options
   // $defaultOptions = array(
   // 'offset' => 0,
   // 'asking_for' => 'META',
   // 'checksum' => self::CHECKSUM_ALL,
   // 'wanted' => self::WANTED_ALL,
   // );

   // $options = array_merge($defaultOptions, $options);

   // $xml = $this->callApi('get_computers_V1', new OcsApiRequest(array(
   // 'ENGINE' => 'FIRST',
   // 'OFFSET' => 0,
   // 'ASKING_FOR' => 'META',
   // 'CHECKSUM' => $options['checksum'],
   // 'WANTED' => $options['wanted'],
   // )));

   // $computerObjs = simplexml_load_string($xml);

   // $computers = array();
   // foreach ($computerObjs as $obj) {
   // $computers []= array(
   // 'ID' => (string) $obj->DATABASEID,
   // 'CHECKSUM' => (string) $obj->CHECKSUM,
   // 'DEVICEID' => (string) $obj->DEVICEID,
   // 'LASTCOME' => (string) $obj->LASTCOME,
   // 'LASTDATE' => (string) $obj->LASTDATE,
   // 'NAME' => (string) $obj->NAME,
   // 'TAG' => (string) $obj->TAG,
   // );
   // }

   // return $computers;
   // }

   // /**
   // * @see OcsClient::getDicoSoftElement()
   // */
   // public function getDicoSoftElement($word) {
   // return $this->callApi('get_dico_soft_element_V1', $word);
   // }

   // /**
   // * @see OcsClient::getHistory()
   // */
   // public function getHistory($offset, $count) {
   // return $this->callApi('get_history_V1', array($offset, $count));
   // }

   // /**
   // * @see OcsClient::clearHistory()
   // */
   // public function clearHistory($offset, $count) {
   // return $this->callApi('clear_history_V1', array($offset, $count));
   // }

   // /**
   // * @see OcsClient::resetChecksum()
   // */
   // public function resetChecksum($checksum, $ids) {
   // $params = array_merge(array($checksum), $ids);
   // return $this->callApi('reset_checksum_V1', $params);
   // }

   // /**
   // * @return SoapClient
   // */
   // public function getSoapClient() {
   // return $this->soapClient;
   // }

   // /**
   // * @param SoapClient $soapClient
   // */
   // public function setSoapClient($soapClient) {
   // $this->soapClient = $soapClient;
   // }

   /**
    *
    * @param string $method
    * @param mixed  $request
    *
    * @return mixed
    */
   //   private function callApi($method, $request) {
   //      if ($request instanceof OcsApiRequest) {
   //         $res = $this->apiClient->$method($request->toXml());
   //      } else if (is_array($request)) {
   //         $res = call_user_func_array(array(
   //                                        $this->apiClient,
   //                                        $method
   //                                     ), $request);
   //      } else {
   //         $res = $this->apiClient->$method($request);
   //      }
   //
   //      if (is_array($res)) {
   //         $res = implode('', $res);
   //      }
   //
   //      return is_string($res) ? trim($res) : $res;
   //   }
   public function getTotalDeletedComputers() {
      // TODO: Implement getTotalDeletedComputers() method.
   }
}
