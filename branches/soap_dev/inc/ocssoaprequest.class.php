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
		
		if (is_array($this->params)) {
		$xml = '<REQUEST>';
			foreach ($this->params as $name => $val) {
				$xml .= "<$name>$val</$name>";
			}
		$xml .= '</REQUEST>';
		} else {
			$xml .= $this->params;
		}
		
		return $xml;
	}
}

?>