<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

include('../../../inc/includes.php');
Session::checkRight("plugin_ocsinventoryng", UPDATE);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "import");

//$soapclient = new PluginOcsinventoryngOcsSoapClient('http://localhost', 'admin', 'factorfx');
$dbclient = new PluginOcsinventoryngOcsDbClient(1, 'ocstest', 'ocsuser', 'ocspass', 'ocsweb');

var_dump($dbclient->getAccountInfoColumns());


/*
$temps = microtime();   
$temps = explode(' ', $temps);   
$debut = $temps[1] + $temps[0];


?>


<p>
####################################################</br>
#############       Computers      #################</br>
####################################################</br>
<?php
$computers = $dbclient->getComputers(array("DISPLAY"=>array("CHECKSUM"=>0x1FFFF,"WANTED"=>0x00003)));

var_dump($computers);


$temps = microtime();
$temps = explode(' ', $temps);
$fin = $temps[1] + $temps[0];
 
// On affiche la différence entre des deux valeurs
echo 'Page exécutée en '.round(($fin - $debut),6).' secondes.';

/*

?>


###############################
###############################
###############################

<?php

var_dump($dbclient->getDeletedComputers());
var_dump($dbclient->searchComputers('DEVICEID',''));
?>


</br>
####################################################</br>
#############      Computer(6)     #################</br>
####################################################</br>
<?php
$computer = $dbclient->getComputers(array(array("ID"=>37),null),"NAME");
echo $computer['ID'] ." => ". $computer['NAME'] ."</br>" ;
?>



</br>
####################################################</br>
############      Account Info(7) #################</br>
####################################################</br>
<?php
$accountinfo = $dbclient->getAccountInfo(7);
print_r($accountinfo);
var_dump($accountinfo);
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


</br>
</br>
####################################################</br>
#######          DChecksum(37)           ##########</br>
####################################################</br>
<?php 
$checksum = $dbclient->getChecksum(37);
var_dump($checksum);*/