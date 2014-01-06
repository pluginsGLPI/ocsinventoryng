<?php

class PluginOcsinventoryngOcsDbClient extends PluginOcsinventoryngOcsClient {
	/**
	 * @var DBmysql
	 */
	private $db;
	
	public function __construct($server_id) {
		$this->db = new PluginOcsinventoryngDBocs($server_id);
	}
	
	/**
	 * @see OcsClientInterface::getComputers()
	 */
	public function getComputers($options = array()) {
		// Get options
		$defaultOptions = array(
			'checksum' => self::CHECKSUM_ALL,
			'wanted' => self::WANTED_ALL,
		);
		
		$options = array_merge($defaultOptions, $options);
		
		$xml = $this->callSoap('get_computers_V1', new PluginOcsinventoryngOcsSoapRequest(array(
			'ENGINE' => 'FIRST',
			'OFFSET' => 0,
			'ASKING_FOR' => 'META',
			'CHECKSUM' => $options['checksum'],
			'WANTED' => $options['wanted'],
		)));
		
		$computers = simplexml_load_string($xml);
		
		return $computers;
	}
	
	/**
	 * @see OcsClientInterface::getOcsConfig()
	 */
	public function getOcsConfig($key) {
		return $this->callSoap('ocs_config_V2', $key);
	}
	
	/**
	 * @see OcsClientInterface::setOcsConfig()
	 */
	public function setOcsConfig($key, $ivalue, $tvalue) {
		return $this->callSoap('ocs_config_V2', array($key, $ivalue, $tvalue));
	}
	
	/**
	 * @see OcsClientInterface::getDicoSoftElement()
	 */
	public function getDicoSoftElement($word) {
		return $this->callSoap('get_dico_soft_element_V1', $word);
	}
	
	/**
	 * @see OcsClientInterface::getHistory()
	 */
	public function getHistory($offset, $count) {
		return $this->callSoap('get_history_V1', array($offset, $count));
	}
	
	/**
	 * @see OcsClientInterface::clearHistory()
	 */
	public function clearHistory($offset, $count) {
		return $this->callSoap('clear_history_V1', array($offset, $count));
	}
	
	/**
	 * @see OcsClientInterface::resetChecksum()
	 */
	public function resetChecksum($checksum, $ids) {
		$params = array_merge(array($checksum), $ids);
		return $this->callSoap('reset_checksum_V1', $params);
	}

	/**
	 * @return SoapClient
	 */
	public function getSoapClient() {
	    return $this->soapClient;
	}

	/**
	 * @param SoapClient $soapClient
	 */
	public function setSoapClient(SoapClient $soapClient) {
	    $this->soapClient = $soapClient;
	}
	
	/**
	 * @param string $method
	 * @param mixed $request
	 * @return mixed
	 */
	private function callSoap($method, $request) {
		if ($request instanceof PluginOcsinventoryngOcsSoapRequest) {
			$res = $this->soapClient->$method($request->toXml());
		} else if (is_array($request)) {
			$res = call_user_func_array(array($this->soapClient, $method), $request);
		} else {
			$res = $this->soapClient->$method($request);
		}
		
		if (is_array($res)) {
			$res = implode('', $res);
		}
		
		return $res;
	}
}

?>