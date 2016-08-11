<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ipdisc
 *
 * @author cmak
 */
include ('../../../inc/includes.php');

Session::checkRight("plugin_ocsinventoryng", READ);
//Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu",'ipdiscmodifynetwork');
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu");
$ip = new PluginOcsinventoryngIpDiscover();

if (isset($_GET["ip"])) {
   $_POST["ip"] = $_GET["ip"];
}
if (isset($_POST["ip"])) {
   $ip       = new PluginOcsinventoryngIpDiscover();
   $ipAdress = $_POST["ip"];
   $values   = array();
   if (isset($_POST["subnetName"]) && isset($_POST["subnetChoise"]) && isset($_POST["SubnetMask"])) {
      $values = array("subnetName" => $_POST["subnetName"], "subnetChoise" => $_POST["subnetChoise"], "subnetMask" => $_POST["SubnetMask"]);
   }
   $ip->modifyNetworkForm($ipAdress, $values);
}

Html::footer();
?>