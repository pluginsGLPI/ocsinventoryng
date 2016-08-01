<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ipdiscover
 *
 * @author cmak
 */
include ('../../../inc/includes.php');
Session::checkRight("plugin_ocsinventoryng", READ);
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ipdiscover");
$ip             = new PluginOcsinventoryngIpDiscover();
if ((isset($_POST["subnetsChoise"]) && isset($_SESSION["subnets"])) || (isset($_SESSION["subnets"]) && $_GET["subnetsChoise"])) {
   $sN             = "";
   $networksDetail = array();
   $ocsServerId    = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
   $tab            = $_SESSION["subnets"];
   $subnets        = $ip->getSubnets($ocsServerId);
   if (isset($_POST["subnetsChoise"])) {
      $sN                              = $tab[$_POST["subnetsChoise"]];
      $networksDetail["subnets"]       = $ip->showSubnets($ocsServerId, $subnets, $sN);
      $networksDetail["subnetsChoise"] = $_POST["subnetsChoise"];
   } else {
      $sN                              = $tab[$_GET["subnetsChoise"]];
      $networksDetail["subnets"]       = $ip->showSubnets($ocsServerId, $subnets, $sN);
      $networksDetail["subnetsChoise"] = $_GET["subnetsChoise"];
   }
   $lim = count($networksDetail["subnets"]);
   if ($lim > $_SESSION["glpilist_limit"]) {
      $ip->showSubnetsDetails($networksDetail, $_SESSION["glpilist_limit"]);
   } else {
      $ip->showSubnetsDetails($networksDetail, $lim);
   }
}
$ip->ipDiscFooter();

?>