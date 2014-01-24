<?php

class PluginOcsinventoryngOcsSoapClient extends PluginOcsinventoryngOcsClient {
	/**
	 * @var SoapClient
	 */
	private $soapClient;
	
	public function __construct($url, $user, $pass) {
		
		$options = array(
			'location' => "$url/ocsinterface",
			'uri' => "$url/Apache/Ocsinventory/Interface",
			'login' => $user,
			'password' => $pass,
			'trace' => true,
			'soap_version' => SOAP_1_1,
			'exceptions' => 0,
		);
		
		$this->soapClient = new SoapClient(null, $options);
	}

	public function getComputers($conditions=array(),$sort=NULL) {}
	public function getAccountInfo($id) {}
	public function getConfig($select="*", $name) {}
	public function getCategorie($table, $condition=1, $sort) {}
	public function getUnique($columns, $table, $conditions, $sort) {}
	public function setChecksum($checksum, $id) {}
    public function delEquiv($deleted, $equivclean = null) {}
	public function getAccountInfoColumns() {}

	// /**
	//  * @see PluginOcsinventoryngOcsClient::getComputers()
	//  */
	// public function getComputers($options = array()) {
	// 	// TODO don't use simplexml
	// 	// TODO paginate
	// 	// Get options
	// 	$defaultOptions = array(
	// 		'offset' => 0,
	// 		'asking_for' => 'META',
	// 		'checksum' => self::CHECKSUM_ALL,
	// 		'wanted' => self::WANTED_ALL,
	// 	);
		
	// 	$options = array_merge($defaultOptions, $options);
		
	// 	$xml = $this->callSoap('get_computers_V1', new PluginOcsinventoryngOcsSoapRequest(array(
	// 		'ENGINE' => 'FIRST',
	// 		'OFFSET' => 0,
	// 		'ASKING_FOR' => 'META',
	// 		'CHECKSUM' => $options['checksum'],
	// 		'WANTED' => $options['wanted'],
	// 	)));
		
	// 	$computerObjs = simplexml_load_string($xml);
		
	// 	$computers = array();
	// 	foreach ($computerObjs as $obj) {
	// 		$computers []= array(
	// 			'ID' => (string) $obj->DATABASEID,
	// 			'CHECKSUM' => (string) $obj->CHECKSUM,
	// 			'DEVICEID' => (string) $obj->DEVICEID,
	// 			'LASTCOME' => (string) $obj->LASTCOME,
	// 			'LASTDATE' => (string) $obj->LASTDATE,
	// 			'NAME' => (string) $obj->NAME,
	// 			'TAG' => (string) $obj->TAG,
	// 		);
	// 	}
		
	// 	return $computers;
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::getOcsConfig()
	//  */
	// public function getOcsConfig($key) {
	// 	$xml = $this->callSoap('ocs_config_V2', $key);
	// 	if ( !is_soap_fault($xml)){
	// 		$configObj = simplexml_load_string($xml);
	// 		$config= array(
	// 			'IVALUE' => (string) $configObj->IVALUE,
	// 			'TVALUE' => (string) $configObj->TVALUE,
	// 		);
	// 		return $config;
	// 	}
	// 	return false;
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::setOcsConfig()
	//  */
	// public function setOcsConfig($key, $ivalue, $tvalue) {
	// 	return $this->callSoap('ocs_config_V2', array($key, $ivalue, $tvalue));
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::getDicoSoftElement()
	//  */
	// public function getDicoSoftElement($word) {
	// 	return $this->callSoap('get_dico_soft_element_V1', $word);
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::getHistory()
	//  */
	// public function getHistory($offset, $count) {
	// 	return $this->callSoap('get_history_V1', array($offset, $count));
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::clearHistory()
	//  */
	// public function clearHistory($offset, $count) {
	// 	return $this->callSoap('clear_history_V1', array($offset, $count));
	// }
	
	// /**
	//  * @see PluginOcsinventoryngOcsClient::resetChecksum()
	//  */
	// public function resetChecksum($checksum, $ids) {
	// 	$params = array_merge(array($checksum), $ids);
	// 	return $this->callSoap('reset_checksum_V1', $params);
	// }

	// /**
	//  * @return SoapClient
	//  */
	// public function getSoapClient() {
	//     return $this->soapClient;
	// }

	// /**
	//  * @param SoapClient $soapClient
	//  */
	// public function setSoapClient($soapClient) {
	//     $this->soapClient = $soapClient;
	// }
	
	// /**
	//  * @param string $method
	//  * @param mixed $request
	//  * @return mixed
	//  */
	// private function callSoap($method, $request) {
	// 	if ($request instanceof PluginOcsinventoryngOcsSoapRequest) {
	// 		$res = $this->soapClient->$method($request->toXml());
	// 	} else if (is_array($request)) {
	// 		$res = call_user_func_array(array($this->soapClient, $method), $request);
	// 	} else {
	// 		$res = $this->soapClient->$method($request);
	// 	}
		
	// 	if (is_array($res)) {
	// 		$res = implode('', $res);
	// 	}
		
	// 	return $res;
	// }
}

?>