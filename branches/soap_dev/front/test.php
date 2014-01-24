<?php

include ('../../../inc/includes.php');

plugin_ocsinventoryng_checkRight("ocsng", "w");

Html::header('OCS Inventory NG', "", "plugins", "ocsinventoryng", "import");

$soapclient = new PluginOcsinventoryngOcsSoapClient('http://localhost', 'admin', 'factorfx');
$dbclient = new PluginOcsinventoryngOcsDbClient(1);

var_dump($dbclient);
?>
<p>
####################################################</br>
#############       Computers      #################</br>
####################################################</br>
<?php
$computers = $dbclient->getComputers(array(array(),array("NAME"=>"\"eze\"") ),"NAME");

foreach ($computers as $id => $cptr) {
		echo $cptr['ID'] ." => ".$cptr['NAME']."</br>" ;
}
?>




</br>
####################################################</br>
#############      Computer(6)     #################</br>
####################################################</br>
<?php
$computer = $dbclient->getComputers(array(array("ID"=>6),null),"NAME");
echo $computer['ID'] ." => ". $computer['NAME'] ."</br>" ;
?>






</br>
####################################################</br>
###############     Account Info   #################</br>
####################################################</br>
<?php
$accountinfo = $dbclient->getAccountInfo(37);
print_r($accountinfo);
?>







</br>
</br>
####################################################</br>
#######    Config(IPDISCOVER_IPD_DIR)      #########</br>
####################################################</br>
<?php
$config = $dbclient->getConfig(array("IVALUE","TVALUE"), "IPDISCOVER_IPD_DIR");
print_r($config);
?>







</br>
</br>
####################################################</br>
#######       Account Info Columns        ##########</br>
####################################################</br>
<?php
$columns = $dbclient->getAccountInfoColumns();
print_r($columns);
?>







</br>
</br>
####################################################</br>
#######          Categorie(cpus)          ##########</br>
####################################################</br>
<?php
$categorie = $dbclient->getCategorie("cpus",1,"TYPE");
print_r($categorie);
?>






</br>
</br>
####################################################</br>
#######          Checksum(80,6)           ##########</br>
####################################################</br>
<?php
$checksum = $dbclient->setChecksum(80,6);
print_r($checksum);
?>






</br>
</br>
####################################################</br>
#######          Del equiv(80,6)           ##########</br>
####################################################</br>
<?php
$delete = $dbclient->delEquiv("OCS-IG-client-debian2-2013-08-26-14-10-04");
print_r($delete);
?>















</p>