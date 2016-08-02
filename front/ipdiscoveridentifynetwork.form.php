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

Session::checkSeveralRightsOr(array("plugin_ocsinventoryng"  => READ,
                                    "plugin_ocsinventoryng_clean"  => READ));

//Session::checkRight("plugin_ocsinventoryng", READ);
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ipdiscmodifynetwork");
$ip=new PluginOcsinventoryngIpDiscover();
if (isset($_GET["ip"])){
$ipAdress=$_GET["ip"];
$values=array();
if (isset($_POST["subnetName"])&&isset($_POST["subnetChoise"])&&isset($_POST["newSubnetId"])&&isset($_POST["subnetMask"])) {
 $values=array("subnetName" => $_POST["subnetName"],"subnetChoise" => $_POST["subnetChoise"],"newId" => $_POST["newSubnetId"],"subnetMask" => $_POST["subnetMask"]);  
}
$ip->modifyNetworkForm($ipAdress,$values);
}
if (isset($_POST["ip"])){
 $ip=new PluginOcsinventoryngIpDiscover();
$ipAdress=$_POST["ip"];
$values=array();
if (isset($_POST["subnetName"])&&isset($_POST["subnetChoise"])&&isset($_POST["newSubnetId"])&&isset($_POST["subnetMask"])) {
 $values=array("subnetName" => $_POST["subnetName"],"subnetChoise" => $_POST["subnetChoise"],"newId" => $_POST["newSubnetId"],"subnetMask" => $_POST["subnetMask"]);  
}
$ip->modifyNetworkForm($ipAdress,$values);
}  
$ip->ipDiscFooter();
?>