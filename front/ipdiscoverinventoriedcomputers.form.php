<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include ('../../../inc/includes.php');
Session::checkRight("plugin_ocsinventoryng", READ);
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu");
//Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ipdiscmodifynetwork");
$ip = new PluginOcsinventoryngIpDiscover();
if (isset($_GET["ip"]) || isset($_POST["ip"])) {
   $ocsServerId=$_SESSION["plugin_ocsinventoryng_ocsservers_id"];
   $status=$_GET["status"];
   $glpiListLimit=$_SESSION["glpilist_limit"];
   $ipAdress = "";
   if (isset($_GET["ip"])) {
      $ipAdress = $_GET["ip"];
   } else {
      $ipAdress = $_POST["ip"];
   }
   $hardware = array();
   $knownMacAdresses=$ip->getKnownMacAdresseFromGlpi();
   if (isset($status)) {
      $hardware = $ip->getHardware($ipAdress, $ocsServerId,$status,$knownMacAdresses);
      $lim      = count($hardware);
      if ($lim > $glpiListLimit) {
         if(isset($_GET["start"])){
         $ip->showHardware($hardware, $glpiListLimit,intval($_GET["start"]), $ipAdress, $status);
         } else {
            $ip->showHardware($hardware, $glpiListLimit,0, $ipAdress, $status);
         }
      } else {
         if(isset($_GET["start"])){
         $ip->showHardware($hardware, $lim,intval($_GET["start"]), $ipAdress, $status);
         } else {
            $ip->showHardware($hardware, $lim,0, $ipAdress, $status);
         }
      }
   } 
}

if (isset($_POST["macToImport"])&&sizeof($_POST["macToImport"]) > 0) {
   $macAdresses=$_POST["macToImport"];
   $itemsTypes=$_POST["itemstypes"];
   $itemsNames=$_POST["itemsname"];
   $itemsDescription=$_POST["itemsdescription"];
   $entities=array();
   if (isset($_POST["entities"])){
   $entities=$_POST["entities"];
   }
   
   $ipObjects=$ip->getIpDiscoverobject($macAdresses,$entities,$itemsTypes,$itemsNames,$itemsDescription);
   $action=null;
   while($ipObject = array_pop($ipObjects)){
   $action=$ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   }
   /*$array=array();
   PluginOcsinventoryngOcsServer::manageImportStatistics($array,
      $action['status'], false);
   PluginOcsinventoryngOcsServer::showStatistics($array, false, false,true); 
   echo "<a href='".$_SERVER['PHP_SELF']."'>".__('Back')."</a></div>"*/
}

html::footer();
?>