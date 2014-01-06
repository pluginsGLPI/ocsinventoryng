<?php

include ('../../../inc/includes.php');

plugin_ocsinventoryng_checkRight("ocsng", "w");

Html::header('OCS Inventory NG', "", "plugins", "ocsinventoryng", "import");

$soapclient = new PluginOcsinventoryngOcsSoapClient('http://localhost', 'admin', 'factorfx');
$dbclient = new PluginOcsinventoryngOcsDbClient($server_id);
print_r($computers = $soapclient->getComputers(array(
	'wanted' => PluginOcsinventoryngOcsClient::WANTED_ALL
)));

print_r($soapclient->getHistory(0,100));
print_r($soapclient->getOcsConfig('LOGPATH'));
var_dump($soapclient->getSoapClient()->__getLastRequest());
var_dump($soapclient->getSoapClient()->__getLastResponse());

?>