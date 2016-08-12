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
   $ipAdress = "";
   if (isset($_GET["ip"])) {
      $ipAdress = $_GET["ip"];
   } else {
      $ipAdress = $_POST["ip"];
   }
   $hardware = array();
   if (isset($_GET["status"])) {
      $hardware = $ip->getHardware($ipAdress, $_SESSION["plugin_ocsinventoryng_ocsservers_id"],$_GET["status"]);
      $lim      = count($hardware);
      if ($lim > $_SESSION["glpilist_limit"]) {
         if(isset($_GET["start"])){
         $ip->showHardware($hardware, $_SESSION["glpilist_limit"],intval($_GET["start"]), $ipAdress, $_GET["status"]);
         } else {
            $ip->showHardware($hardware, $_SESSION["glpilist_limit"],0, $ipAdress, $_GET["status"]);
         }
      } else {
         if(isset($_GET["start"])){
         $ip->showHardware($hardware, $lim,intval($_GET["start"]), $ipAdress, $_GET["status"]);
         } else {
            $ip->showHardware($hardware, $lim,0, $ipAdress, $_GET["status"]);
         }
      }
   } 
}

if (isset($_POST["macToImport"])&&sizeof($_POST["macToImport"]) > 0) {
   var_dump($_POST);
   $macAdresses=$_POST["macToImport"];
   $itemsTypes=$_POST["itemstypes"];
   $itemsNames=$_POST["itemsname"];
   $entities=array();
   if (isset($_POST["entities"])){
   $entities=$_POST["entities"];
   }
   
   var_dump($entities,  empty($entities));
   $ipObjects=$ip->getIpDiscoverobject($macAdresses,$entities,$itemsTypes,$itemsNames);
   
   while($ipObject = array_pop($ipObjects)){
   $action[]=$ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   }
   /*if(!empty($action)){
      var_dump($action,"Printer Imported");
   }*/
   
}

html::footer();
?>