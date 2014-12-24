<?php

class PluginOcsinventoryngOcsSoapRequest {
	/**
	 * @var mixed
	 */
	private $params;
	
	/**
	 * @param mixed $params
	 */
	public function __construct($params) {
		$this->params = $params;
	}
	
	/**
	 * @return string
	 */
	public function toXml() {
		return $this->_toXml('REQUEST', $this->params);
	}
	
	private function _toXml($tagName, $value) {
		$xml = '';
		
		if (is_array($value)) {
			if ($this->isIndexed($value)) {
				foreach ($value as $val) {
					$xml .= $this->_toXml($tagName, $val);
				}
			} else {
				$xml .= "<$tagName>";
				foreach ($value as $key => $val) {
					$xml .= $this->_toXml($key, $val);
				}
				$xml .= "</$tagName>";
			}
		} else {
			$xml .= "<$tagName>$value</$tagName>";
		}
		
		return $xml;
	}
	
	private function isIndexed($array) {
		return (bool) count(array_filter(array_keys($array), 'is_numeric'));
	}
}

?>