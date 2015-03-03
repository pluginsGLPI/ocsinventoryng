<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
 -------------------------------------------------------------------------
 Ocsinventoryng plugin for GLPI
 Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

 https://forge.indepnet.net/projects/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 Ocsinventoryng plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Ocsinventoryng plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 -------------------------------------------------------------------------- */

class PluginOcsinventoryngOcsSoapClient extends PluginOcsinventoryngOcsClient {
   /**
    * @var SoapClient
    */
   private $soapClient;

   public function __construct($id, $url, $user, $pass) {
      parent::__construct($id);
      
      $options = array(
         'location' => "$url/ocsinterface",
         'uri' => "$url/Apache/Ocsinventory/Interface",
         'login' => $user,
         'password' => $pass,
         'trace' => true,
         'soap_version' => SOAP_1_1,
         'exceptions' => 0
      );
      
      $this->soapClient = new SoapClient(null, $options);
   }

   /**
    * @see PluginOcsinventoryngOcsClient::checkConnection()
    */
   public function checkConnection() {
      return !is_soap_fault($this->soapClient->ocs_config_V2('LOGLEVEL'));
   }
   
   public function searchComputers($field, $value) {
      $xml = $this->callSoap('search_computers_V1', array($field, $value));

      $computerObjs = simplexml_load_string($xml);

      $computers = array();
      if (count($computerObjs) > 0) {
         foreach ($computerObjs as $obj) {
            $computers []= array(
               'ID' => (int) $obj->DATABASEID,
               'CHECKSUM' => (int) $obj->CHECKSUM,
               'DEVICEID' => (string) $obj->DEVICEID,
               'LASTCOME' => (string) $obj->LASTCOME,
               'LASTDATE' => (string) $obj->LASTDATE,
               'NAME' => (string) $obj->NAME,
               'TAG' => (string) $obj->TAG
            );
         }
      }
      return $computers;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getComputers()
    */
   public function getComputers($options) {
      $offset = $originalOffset = isset($options['OFFSET']) ? (int) $options['OFFSET'] : 0;
      $maxRecords = isset($options['MAX_RECORDS']) ? (int) $options['MAX_RECORDS'] : null;
      $checksum = isset($options['CHECKSUM']) ? (int) $options['CHECKSUM'] : 131071;
      $wanted = isset($options['WANTED']) ? (int) $options['WANTED'] : 131071;
      $asking_for = isset($options['"ASKING_FOR"']) ? $options['ASKING_FOR'] : "INVENTORY";
      $engine = isset($options['"ENGINE"']) ? $options['ENGINE'] : "FIRST";
      
      $originalEnd = isset($options['MAX_RECORDS']) ? $originalOffset + $maxRecords : null;
      $ocsMap = $this->getOcsMap();
      
      $computers = array();

      do {
         $options['ENGINE'] = $engine;
         $options['ASKING_FOR'] = $asking_for;
         $options['CHECKSUM'] = $checksum;
         $options['OFFSET'] = $offset;
         if (!is_null($maxRecords)) {
            $options['MAX_RECORDS'] = $maxRecords;
         }
         
         $xml = $this->callSoap('get_computers_V1', new PluginOcsinventoryngOcsSoapRequest($options));

         $computerObjs = array();
         $computerObjs = simplexml_load_string($xml);

         if (count($computerObjs) > 0){
            foreach ($computerObjs as $obj) {
            
               //toolbox::logdebug($obj);
               //die();
               
               $meta = $obj->META;
               
               $computer = array(
                  'META' => array(
                     'ID' => (int) $meta->DATABASEID,
                     'CHECKSUM' => (int) $meta->CHECKSUM,
                     'DEVICEID' => (string) $meta->DEVICEID,
                     'LASTCOME' => (string) $meta->LASTCOME,
                     'LASTDATE' => (string) $meta->LASTDATE,
                     'NAME' => (string) $meta->NAME,
                     'TAG' => (string) $meta->TAG
                  )
               );
               
               
               foreach ($obj->children() as $sectionName => $sectionObj) {
                  $section = array();
                  $special_sections = array('ACCOUNTINFO','DICO_SOFT');
                  foreach ($sectionObj as $key => $val) {
                     if(in_array($sectionName,$special_sections)){
                        $section[(string)$val->attributes()->Name]= (string) $val;
                     }else{
                        $section[$key] = (string) $val;
                     }
                  }
                  if (!isset($computer[$sectionName])) {
                     $computer[$sectionName] = array();
                  }
                  
                  $lowerSectionName = strtolower($sectionName);
                  if (isset($ocsMap[$lowerSectionName]) and !$ocsMap[$lowerSectionName]['multi']) {
                     $computer[$sectionName] = $section;
                  } else {
                     $computer[$sectionName] []= $section;
                  }
               }
               if(array_key_exists('ACCOUNTINFO', $computer)){
                  foreach ($computer['ACCOUNTINFO'] as $number =>$accountinfo){
                     $computer['ACCOUNTINFO'][$number]['HARDWARE_ID']= $computer['META']['ID'];
                  }
               }
               $computers[(int) $meta->DATABASEID] = $computer;
            }
         }
         $totalCount = (int) $computerObjs['TOTAL_COUNT'];
         $maxRecords = (int) $computerObjs['MAX_RECORDS'];
         $offset += $maxRecords;
         
         // We can't load more records than there is in ocs
         $end = (is_null($originalEnd) or $originalEnd > $totalCount) ? $totalCount : $originalEnd;
         
         if ($offset + $maxRecords > $end) {
            $maxRecords = $end - $offset;
         }
      } while ($options['OFFSET'] + $computerObjs['MAX_RECORDS'] < $end);
      
      //toolbox::logdebug($computers);
      return array(
         'TOTAL_COUNT' => $totalCount,
         'COMPUTERS' => $computers
      );
   }
   
   public function getComputerSections($ids, $checksum = self::CHECKSUM_ALL, $wanted = self::WANTED_ALL) {
      $xml = $this->callSoap('get_computer_sections_V1', array($ids, $checksum, $wanted));

      $computerObjs = simplexml_load_string($xml);
      
      $computers = array();
      foreach ($computerObjs as $obj) {
         $id = (int) $obj['ID'];
         $computer = array();
         
         foreach ($obj as $sectionName => $sectionObj) {
            $computer[$sectionName] = array();
            foreach ($sectionObj as $key => $val) {
               $computer[$sectionName][$key] = (string) $val;
            }
         }
         
         $computers[$id] = $computer;
      }
      
      return $computers;
   }

   public function getAccountInfo($id) {}

   /**
    * @see PluginOcsinventoryngOcsClient::getConfig()
    */
   public function getConfig($key) {
      $xml = $this->callSoap('ocs_config_V2', $key);
      if (!is_soap_fault($xml)) {
         $configObj = simplexml_load_string($xml);
         $config = array(
            'IVALUE' => (int) $configObj->IVALUE,
            'TVALUE' => (string) $configObj->TVALUE
         );
         return $config;
      }
      return false;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::setConfig()
    */
   public function setConfig($key, $ivalue, $tvalue) {
      $this->callSoap('ocs_config_V2', array(
         $key,
         $ivalue,
         $tvalue
      ));
   }
   
   public function getDeletedComputers() {
      $deletedObjs = array();
      //$xml = $this->callSoap('get_deleted_computers_V1', new PluginOcsinventoryngOcsSoapRequest(array()));
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
   }

   public function removeDeletedComputers($deleted, $equiv = null) {
      $count=0;
      if(is_array($deleted)){
         foreach ( $deleted as $del){
            $this->callSoap('remove_deleted_computer_V1', array($del));
            $count++;
         }
      }else{
         if ($equiv) {
            $this->callSoap('remove_deleted_computer_V1', array($deleted, $equiv));
            $count++;
         } else {
            $this->callSoap('remove_deleted_computer_V1', array($deleted));
            $count++;
         }
      }
      $_SESSION["ocs_deleted_equiv"]['computers_deleted']+=$count;
   }


   public function getUnique($columns, $table, $conditions, $sort) {}

   public function setChecksum($checksum, $id) {
      $this->callSoap('reset_checksum_V1', array($checksum, $id));
   }
   
   public function getChecksum($id) {
      $xml = $this->callSoap('get_checksum_V1', $id);
      return (int) simplexml_load_string($xml);
   }


   /**
    * @see PluginOcsinventoryngOcsClient::getAccountInfoColumns()
    */
   public function getAccountInfoColumns() {
      $xml  = $this->callSoap('_get_account_fields_V1', new PluginOcsinventoryngOcsSoapRequest());
      $res = array(
            'HARDWARE_ID' => 'HARDWARE_ID',
            'TAG' =>  'TAG'
      );
      $res = array_merge($res,(array) $xml);
      return $res;
   }
   


   //****************************
      //TO IMPLEMENT 
      //****************************

   

   /**
    * @see PluginOcsinventoryngOcsClient::updateBios()
    */
      public function updateBios($ssn,$id){
         
         
      }

      /**
    * @see PluginOcsinventoryngOcsClient::updateTag()
    */
      public function updateTag($tag,$id){}


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
    * @param mixed $request        	
    * @return mixed
    */
   private function callSoap($method, $request) {
      if ($request instanceof PluginOcsinventoryngOcsSoapRequest) {
         $res = $this->soapClient->$method($request->toXml());
      } else if (is_array($request)) {
         $res = call_user_func_array(array(
            $this->soapClient,
            $method
         ), $request);
      } else {
         $res = $this->soapClient->$method($request);
      }
      
      if (is_array($res)) {
         $res = implode('', $res);
      }
      
      return is_string($res) ? trim($res) : $res;
   }
}

?>