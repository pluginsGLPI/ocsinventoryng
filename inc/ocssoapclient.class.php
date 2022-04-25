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
 * Class PluginOcsinventoryngOcsSoapClient
 */
class PluginOcsinventoryngOcsSoapClient extends PluginOcsinventoryngOcsClient {
   /**
    * @var SoapClient
    */
   private $soapClient;

   /**
    * PluginOcsinventoryngOcsSoapClient constructor.
    *
    * @param $id
    * @param $url
    * @param $user
    * @param $pass
    */
   public function __construct($id, $url, $user, $pass) {
      parent::__construct($id);

      $options = [
         'location'     => "$url/ocsinterface",
         'uri'          => "$url/Apache/Ocsinventory/Interface",
         'login'        => $user,
         'password'     => $pass,
         'trace'        => true,
         'soap_version' => SOAP_1_1,
         'exceptions'   => 0
      ];

      $this->soapClient = new SoapClient(null, $options);
   }

   /**
    * @see PluginOcsinventoryngOcsClient::checkConnection()
    */
   public function checkConnection() {
      return !is_soap_fault($this->soapClient->ocs_config_V2('LOGLEVEL'));
   }

   /**
    * @param string $field
    * @param mixed  $value
    *
    * @return array
    */
   public function searchComputers($field, $value) {
      $xml = $this->callSoap('search_computers_V1', [$field, $value]);

      $computerObjs = simplexml_load_string($xml);

      $computers = [];
      if (count($computerObjs) > 0) {
         foreach ($computerObjs as $obj) {
            $computers [] = [
               'ID'       => (int)$obj->DATABASEID,
               'CHECKSUM' => (int)$obj->CHECKSUM,
               'DEVICEID' => (string)$obj->DEVICEID,
               'LASTCOME' => (string)$obj->LASTCOME,
               'LASTDATE' => (string)$obj->LASTDATE,
               'NAME'     => (string)$obj->NAME,
               'TAG'      => (string)$obj->TAG
            ];
         }
      }
      return $computers;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getComputers()
    *
    * @param array $options
    *
    * @param int   $id
    *
    * @return array
    */
   public function getComputers($options, $id = 0) {
      $offset     = $originalOffset = isset($options['OFFSET']) ? (int)$options['OFFSET'] : 0;
      $maxRecords = isset($options['MAX_RECORDS']) ? (int)$options['MAX_RECORDS'] : null;
      $checksum   = isset($options['CHECKSUM']) ? (int)$options['CHECKSUM'] : 131071;
      $wanted     = isset($options['WANTED']) ? (int)$options['WANTED'] : 131071;
      $asking_for = isset($options['"ASKING_FOR"']) ? $options['ASKING_FOR'] : "INVENTORY";
      $engine     = isset($options['"ENGINE"']) ? $options['ENGINE'] : "FIRST";

      $originalEnd = isset($options['MAX_RECORDS']) ? $originalOffset + $maxRecords : null;
      $ocsMap      = $this->getOcsMap();

      $computers = [];

      do {
         $options['ENGINE']     = $engine;
         $options['ASKING_FOR'] = $asking_for;
         $options['CHECKSUM']   = $checksum;
         $options['OFFSET']     = $offset;
         if (!is_null($maxRecords)) {
            $options['MAX_RECORDS'] = $maxRecords;
         }

         $xml = $this->callSoap('get_computers_V1', new PluginOcsinventoryngOcsSoapRequest($options));

         $computerObjs = [];
         $computerObjs = simplexml_load_string($xml);

         if (count($computerObjs) > 0) {
            foreach ($computerObjs as $obj) {

               //toolbox::logdebug($obj);
               //die();

               $meta = $obj->META;

               $computer = [
                  'META' => [
                     'ID'       => (int)$meta->DATABASEID,
                     'CHECKSUM' => (int)$meta->CHECKSUM,
                     'DEVICEID' => (string)$meta->DEVICEID,
                     'LASTCOME' => (string)$meta->LASTCOME,
                     'LASTDATE' => (string)$meta->LASTDATE,
                     'NAME'     => (string)$meta->NAME,
                     'TAG'      => (string)$meta->TAG
                  ]
               ];

               foreach ($obj->children() as $sectionName => $sectionObj) {
                  $section          = [];
                  $special_sections = ['ACCOUNTINFO', 'DICO_SOFT'];
                  foreach ($sectionObj as $key => $val) {
                     if (in_array($sectionName, $special_sections)) {
                        $section[(string)$val->attributes()->Name] = (string)$val;
                     } else {
                        $section[$key] = (string)$val;
                     }
                  }
                  if (!isset($computer[$sectionName])) {
                     $computer[$sectionName] = [];
                  }

                  $lowerSectionName = strtolower($sectionName);
                  if (isset($ocsMap[$lowerSectionName]) and !$ocsMap[$lowerSectionName]['multi']) {
                     $computer[$sectionName] = $section;
                  } else {
                     $computer[$sectionName] [] = $section;
                  }
               }
               if (array_key_exists('ACCOUNTINFO', $computer)) {
                  foreach ($computer['ACCOUNTINFO'] as $number => $accountinfo) {
                     $computer['ACCOUNTINFO'][$number]['HARDWARE_ID'] = $computer['META']['ID'];
                  }
               }
               $computers[(int)$meta->DATABASEID] = $computer;
            }
         }
         $totalCount = (int)$computerObjs['TOTAL_COUNT'];
         $maxRecords = (int)$computerObjs['MAX_RECORDS'];
         $offset     += $maxRecords;

         // We can't load more records than there is in ocs
         $end = (is_null($originalEnd) or $originalEnd > $totalCount) ? $totalCount : $originalEnd;

         if ($offset + $maxRecords > $end) {
            $maxRecords = $end - $offset;
         }
      } while ($options['OFFSET'] + $computerObjs['MAX_RECORDS'] < $end);

      //toolbox::logdebug($computers);
      return [
         'TOTAL_COUNT' => $totalCount,
         'COMPUTERS'   => $computers
      ];
   }

   /**
    * @param     $ids
    * @param int $checksum
    * @param int $wanted
    *
    * @return array
    */
   public function getComputerSections($ids, $checksum = self::CHECKSUM_ALL, $wanted = self::WANTED_ALL) {
      $xml = $this->callSoap('get_computer_sections_V1', [$ids, $checksum, $wanted]);

      $computerObjs = simplexml_load_string($xml);

      $computers = [];
      foreach ($computerObjs as $obj) {
         $id       = (int)$obj['ID'];
         $computer = [];

         foreach ($obj as $sectionName => $sectionObj) {
            $computer[$sectionName] = [];
            foreach ($sectionObj as $key => $val) {
               $computer[$sectionName][$key] = (string)$val;
            }
         }

         $computers[$id] = $computer;
      }

      return $computers;
   }

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
    *            'CHECKSUM' => int               // filter which sections have been modified (see
    *    CHECKSUM_* constants)
    *         ),
    *         'DISPLAY' => array(      // select which sections of the computers to return
    *            'CHECKSUM' => int,   // inventory sections to return (see CHECKSUM_* constants)
    *            'WANTED' => int      // special sections to return (see WANTED_* constants)
    *         )
    *      )
    *
    * @return void List of snmp devices :
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
   public function getSnmp($options) {
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getConfig()
    *
    * @param string $key
    *
    * @return array|bool|mixed
    */
   public function getConfig($key) {
      $xml = $this->callSoap('ocs_config_V2', $key);
      if (!is_soap_fault($xml)) {
         $configObj = simplexml_load_string($xml);
         $config    = [
            'IVALUE' => (int)$configObj->IVALUE,
            'TVALUE' => (string)$configObj->TVALUE
         ];
         return $config;
      }
      return false;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::setConfig()
    *
    * @param string $key
    * @param int    $ivalue
    * @param string $tvalue
    */
   public function setConfig($key, $ivalue, $tvalue) {
      $this->callSoap('ocs_config_V2', [
         $key,
         $ivalue,
         $tvalue
      ]);
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
      $deletedObjs = [];
      //$xml = $this->callSoap('get_deleted_computers_V1', new PluginOcsinventoryngOcsSoapRequest(array()));
      //$deletedObjs = simplexml_load_string($xml);
      $res = [];

      /* if (is_array($deletedObjs) && count($deletedObjs) > 0) {
          foreach ($deletedObjs as $obj) {
             $res[(string) $obj['DELETED']] = (string) $obj['EQUIVALENT'];
          }
       }
       if($res != array()){
          $_SESSION["ocs_deleted_equiv"]['computers_to_del']=true;
       }
       return $res;*/
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
            $this->callSoap('remove_deleted_computer_V1', [$del]);
            $count++;
         }
      } else {
         if ($equiv) {
            $this->callSoap('remove_deleted_computer_V1', [$deleted, $equiv]);
            $count++;
         } else {
            $this->callSoap('remove_deleted_computer_V1', [$deleted]);
            $count++;
         }
      }
      $_SESSION["ocs_deleted_equiv"]['computers_deleted'] += $count;
   }


   /**
    * Get the old agents without inventory in ocsinventory
    *
    * @return void The list of deleted computers : (DELETED contains the id or deviceid of the
    *    computer and equivalent and EQUIV contains the new id if the computer was marged) array (
    *         'DELETED' => 'EQUIV'
    *      )
    */
   public function getOldAgents($nb_days) {
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
      $this->callSoap('reset_checksum_V1', [$checksum, $id]);
   }

   /**
    * @param int $id
    *
    * @return int
    */
   public function getChecksum($id) {
      $xml = $this->callSoap('get_checksum_V1', $id);
      return (int)simplexml_load_string($xml);
   }


   /**
    * @see PluginOcsinventoryngOcsClient::getAccountInfoColumns()
    *
    * @param $table
    *
    * @return array
    */
   public function getAccountInfoColumns($table) {
      $xml = $this->callSoap('_get_account_fields_V1', new PluginOcsinventoryngOcsSoapRequest());
      $res = [
         'HARDWARE_ID' => 'HARDWARE_ID',
         'TAG'         => 'TAG'
      ];
      $res = array_merge($res, (array)$xml);
      return $res;
   }



   //****************************
   //TO IMPLEMENT
   //****************************


   /**
    * @see PluginOcsinventoryngOcsClient::updateBios()
    *
    * @param int $ssn
    * @param int $id
    */
   public function updateBios($ssn, $id) {

   }

   /**
    * @see PluginOcsinventoryngOcsClient::updateTag()
    *
    * @param int $tag
    * @param int $id
    */
   public function updateTag($tag, $id) {
   }


   // /**
   // * @see PluginOcsinventoryngOcsClient::getComputers()
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

   // $xml = $this->callSoap('get_computers_V1', new PluginOcsinventoryngOcsSoapRequest(array(
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
   // * @see PluginOcsinventoryngOcsClient::getDicoSoftElement()
   // */
   // public function getDicoSoftElement($word) {
   // return $this->callSoap('get_dico_soft_element_V1', $word);
   // }

   // /**
   // * @see PluginOcsinventoryngOcsClient::getHistory()
   // */
   // public function getHistory($offset, $count) {
   // return $this->callSoap('get_history_V1', array($offset, $count));
   // }

   // /**
   // * @see PluginOcsinventoryngOcsClient::clearHistory()
   // */
   // public function clearHistory($offset, $count) {
   // return $this->callSoap('clear_history_V1', array($offset, $count));
   // }

   // /**
   // * @see PluginOcsinventoryngOcsClient::resetChecksum()
   // */
   // public function resetChecksum($checksum, $ids) {
   // $params = array_merge(array($checksum), $ids);
   // return $this->callSoap('reset_checksum_V1', $params);
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
   private function callSoap($method, $request) {
      if ($request instanceof PluginOcsinventoryngOcsSoapRequest) {
         $res = $this->soapClient->$method($request->toXml());
      } else if (is_array($request)) {
         $res = call_user_func_array([
                                        $this->soapClient,
                                        $method
                                     ], $request);
      } else {
         $res = $this->soapClient->$method($request);
      }

      if (is_array($res)) {
         $res = implode('', $res);
      }

      return is_string($res) ? trim($res) : $res;
   }

   public function getTotalDeletedComputers() {
      // TODO: Implement getTotalDeletedComputers() method.
   }
}
