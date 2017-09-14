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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngIpdiscoverOcslink
 */
class PluginOcsinventoryngIpdiscoverOcslink extends CommonDBTM
{

   static $hardwareItemTypes = array('Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');

   /**
    * @param int $nb
    * @return string|translated
    */
   static function getTypeName($nb = 0)
   {
      return __('IPDiscover Import', 'ocsinventoryng');
   }


   /**
    * parse array with ip or mac into one string
    * @param array|type $array
    * @return string $
    */
   public static function parseArrayToString($array = array())
   {
      $token = "";
      if (sizeof($array) == 0) {
         return "''";
      }
      for ($i = 0; $i < sizeof($array); $i++) {
         if ($i == sizeof($array) - 1) {
            $token .= "'" . $array[$i] . "'";
         } else {
            $token .= "'" . $array[$i] . "'" . ",";
         }
      }
      return $token;
   }

   /**
    * get subnets name
    * @param type $inputs array
    * @param type $outputs array
    */
   public static function getSubnetsName($inputs, &$outputs)
   {

      foreach ($inputs as $subnets) {
         if (isset($subnets["NAME"])) {
            if ($subnets["NAME"] != null) {
               $outputs[] = $subnets["NAME"];
            }
         }
      }
   }

   /**
    * get all the subnets ID
    * @param type outputs array
    */
   public static function getAllSubnetsID(&$outputs)
   {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query = "SELECT `config`.`TVALUE`
              FROM `config` 
              WHERE 
              `config`.`NAME` 
              LIKE 'ID_IPDISCOVER_%'";
      $result = $DBOCS->query($query);
      while ($subNetId = $DBOCS->fetch_assoc($result)) {
         $outputs[$subNetId["TVALUE"]] = $subNetId["TVALUE"];
      }
   }

   /**
    * @param $inputs
    * @param $outputs
    */
   public static function getSubnetsID($inputs, &$outputs)
   {
      foreach ($inputs as $subnets) {
         if (isset($subnets["ID"])) {
            if ($subnets["ID"] != null
               && !in_array($subnets["ID"], $outputs)
            ) {
               $outputs[$subnets["ID"]] = $subnets["ID"];
            }
         }
      }
   }

   /**
    * @param $ipAdress
    * @return int|mixed
    */
   static function getSubnetIDbyIP($ipAdress)
   {

      $subnet = -1;
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsdb = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $OCSDB = $ocsdb->getDB();

      $query = "SELECT *
              FROM subnet
              WHERE `subnet`.`NETID` = '$ipAdress'";
      $result = $OCSDB->query($query);
      if ($result->num_rows > 0) {
         $res = $OCSDB->fetch_assoc($result);
         $tab = $_SESSION["subnets"];
         $subnet = array_search($res["ID"], $tab);

      }
      return $subnet;
   }

   /**
    * @param $ipAdress
    * @return string
    */
   static function getSubnetNamebyIP($ipAdress)
   {

      $name = "";
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsdb = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $OCSDB = $ocsdb->getDB();

      $query = "SELECT *
              FROM `subnet`
              WHERE `subnet`.`NETID` = '$ipAdress'";
      $result = $OCSDB->query($query);
      if ($result->num_rows > 0) {
         $res = $OCSDB->fetch_assoc($result);
         $name = $res["NAME"];

      }
      return $name;
   }

   /**
    * @param $count
    */
   public static function countSubnetsID(&$count)
   {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query = "SELECT MAX(`config`.`IVALUE`) as MAX
              FROM `config` 
              WHERE 
              `config`.`NAME` 
              LIKE 'ID_IPDISCOVER_%'";
      $result = $DBOCS->query($query);
      $subNetId = $DBOCS->fetch_assoc($result);
      $count = intval($subNetId["MAX"]);
   }


   /**
    * get the OCS types from DB
    * @param type $out array
    */
   public static function getOCSTypes(&$out)
   {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query = "SELECT `devicetype`.`id` , `devicetype`.`name`
             FROM `devicetype`";
      $result = $DBOCS->query($query);

      while ($ent = $DBOCS->fetch_assoc($result)) {
         $out["id"][] = $ent["id"];
         $out["name"][] = $ent["name"];
      }
   }


   /**
    * get all the subnets informations
    * @param type $plugin_ocsinventoryng_ocsservers_id
    * @return array with All Subnets , Known Subnets, Unknown Subnets, knownIP, unknownIP
    */
   public static function getSubnets($plugin_ocsinventoryng_ocsservers_id)
   {
      $subnets = array();
      $unknown = array();
      $known = array();
      $unknownIP = array();
      $knownIP = array();
      //$IP        = array();
      $query = "SELECT DISTINCT `networks`.`IPSUBNET`,`subnet`.`NAME`,`subnet`.`ID`
               FROM `networks` 
               LEFT JOIN `subnet` 
               ON (`networks`.`IPSUBNET` = `subnet`.`NETID`) ,`accountinfo`
               WHERE `networks`.`HARDWARE_ID`=`accountinfo`.`HARDWARE_ID`
               AND `networks`.`STATUS`='Up'";
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $result = $DBOCS->query($query);
      while ($subNet = $DBOCS->fetch_assoc($result)) {
         $subnets[] = $subNet;
      }

      for ($i = 0; $i < count($subnets); $i++) {
         if ($subnets[$i]["NAME"] == null) {
            $unknown[] = $subnets[$i];
            $unknownIP[] = $subnets[$i]["IPSUBNET"];
         }
      }
      for ($i = 0; $i < count($subnets); $i++) {
         if ($subnets[$i]["NAME"] != null) {
            $known[] = $subnets[$i];
            $knownIP[] = $subnets[$i]["IPSUBNET"];
         }
      }
      return array("All Subnets" => $subnets,
         "Known Subnets" => $known,
         "Unknown Subnets" => $unknown,
         "knownIP" => $knownIP,
         "unknownIP" => $unknownIP);
   }


   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $subnets
    * @param $knownMacAdresses
    * @param string $option
    * @return array
    */
   public static function showSubnets($plugin_ocsinventoryng_ocsservers_id, $subnets, $knownMacAdresses, $option = "")
   {
      //this query displays all the elements on the the networks we found :
      $subnetsDetails = array();
      $knownNets = "";
      $unknownNets = "";
      $Nets = "";
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      if ($option == "1") {
         $knownNets = self::parseArrayToString($subnets["knownIP"]);
         $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
         $Nets = $knownNets . "," . $unknownNets;
      } else if ($option == "2") {
         $knownNets = self::parseArrayToString($subnets["knownIP"]);
         $Nets = $knownNets;
      } else if ($option == "3") {
         $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
         $Nets = $unknownNets;
      } else {
         if ($option != "") {

            $theSubnet = array();
            $getKnownSubnet = "SELECT `subnet`.`NETID`
                          FROM `subnet`
                          WHERE `ID` LIKE '$option'";
            $result = $DBOCS->query($getKnownSubnet);

            $i = 0;
            while ($subNet = $DBOCS->fetch_assoc($result)) {
               $subnet[] = $subNet;
               $theSubnet[$i] = $subnet[$i]["NETID"];
               $i++;
            }
            $Nets = self::parseArrayToString($theSubnet);
         }
      }
      if ($Nets == "") {
         return array();
      } else {
         $macAdresses = self::parseArrayToString($knownMacAdresses);
         $percentQuery = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME', CASE WHEN ident.c IS NULL and ipdiscover.c IS NULL THEN 100 WHEN ident.c IS NULL THEN 0 WHEN non_ident.c IS NULL THEN 0 ELSE round(100-(non_ident.c*100/(ident.c+non_ident.c)), 1) END as 'PERCENT' 
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover 
right join 
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices ) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";
         //this is for the right percentage
         $percent = $DBOCS->query($percentQuery);

         $query = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME'
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover 
right join 
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices WHERE `network_devices`.`MACADDR` NOT IN($macAdresses)) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";

         $result = $DBOCS->query($query);
         while ($details = $DBOCS->fetch_assoc($result)) {
            $per = $DBOCS->fetch_assoc($percent);
            $details['PERCENT'] = $per['PERCENT'];
            $subnetsDetails[] = $details;
         }
         return $subnetsDetails;
      }
   }

   static function ipDiscoverMenu()
   {
      global $CFG_GLPI, $DB;
      $ocsservers = array();
      //echo "<div class='center'>";
      //echo "<img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/ocsinventoryng.png' " .
      //"alt='OCS Inventory NG' title='OCS Inventory NG'>";
      //echo "</div>";
      $numberActiveServers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', "`is_active`='1'");
      if ($numberActiveServers > 0) {
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php\"
         method='post'>";
         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') .
            "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                   LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . " AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`='1'
                   ORDER BY `name` ASC";
         foreach ($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer', array("condition" => "`id` IN ('" . implode("','", $ocsservers) . "')",
            "value" => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            "on_change" => "this.form.submit()",
            "display_emptychoice" => false));

         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();

         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         self::showSubnetSearchForm("import");
         echo "</td><td class='center'>";
         self::showSubnetSearchForm("link");
         echo "</td></tr>";
         echo "</table></div>";
      }
   }

   /**
    * @param $action
    */
   static function showSubnetSearchForm($action)
   {
      global $CFG_GLPI;

      echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?action=$action\"
                method='post'>";
      echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
      if ($action == "import") {
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Import IPDiscover', 'ocsinventoryng') .
            "</th></tr>\n";
      } else {
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Link IPDiscover', 'ocsinventoryng') .
            "</th></tr>\n";
      }
      //echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an subnet', 'ocsinventoryng') .
      //"</th></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Subnet', 'ocsinventoryng') . "</td>";
      echo "<td class='center'>";
      $tab = array('0' => Dropdown::EMPTY_VALUE,
         '1' => __('All Subnets', 'ocsinventoryng'),
         '2' => __('Known Subnets', 'ocsinventoryng'),
         '3' => __('Unknown Subnets', 'ocsinventoryng'));

      $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      self::getSubnetsID($subnets["All Subnets"], $tab);
      $_SESSION["subnets"] = $tab;
      Dropdown::showFromArray("subnetsChoice", $tab, array("on_change" => "this.form.submit()", "display_emptychoice" => false));
      echo "</td></tr>";
      /*echo "<tr class='tab_bg_1'>";
      echo "<td class='center'><a href='.form.php'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/import.png' " .
      "alt='" . __s("Manage Subnets ID", 'ocsinventoryng') . "' " .
      "title=\"" . __s("Manage Subnets ID", 'ocsinventoryng') . "\">
                     <br>" . __("Manage Subnets ID", 'ocsinventoryng') . "
                  </a></td>";
      echo "<td class='center'><a href='.form.php'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/import.png' " .
      "alt='" . __s("Manage Ocsserver Types", 'ocsinventoryng') . "' " .
      "title=\"" . __s("Manage Ocsserver Types", 'ocsinventoryng') . "\">
                     <br>" . __("Manage Ocsserver Types", 'ocsinventoryng') . "
                  </a></td></tr>";*/
      echo "</table></div>";
      Html::closeForm();
   }

   /*static function showPercentItem($value, $linkto = "") {

      $width = "200px";
      $out   = "<td class='tab_bg_2 rowHover'>";
      if (!empty($linkto)) {
         $out .="<img src=\"$linkto\" alt = \"percentage\" width=\"$value px\" height=\"10px\" ";
      }
      if (!empty($linkto)) {
         $out .= ";>";
      }
      $out .= $value . "%";
      $out .= "</td>\n";
      return $out;
   }*/

   /**
    * @param $value
    * @param string $linkto
    * @param string $id
    * @param string $type
    * @param bool $checkbox
    * @param string $check
    * @param int $iterator
    * @return string
    */
   static function showItem($value, $linkto = "", $id = "", $type = "", $checkbox = false, $check = "", $iterator = 0)
   {
      $out = "<td>";
      if ($checkbox) {
         $out .= "<input type='checkbox' name='mactoimport[$iterator][" . $value . "]' " .
            ($check == "all" ? "checked" : "") . ">\n";

         $out .= "</td>\n";
         return $out;
      } else {

         if (!empty($linkto)) {
            $out .= "<a href=\"$linkto" . "?ip=$id&status=$type\">";
         }

         $out .= $value;
         if (!empty($linkto)) {
            $out .= "</a>";
         }

         $out .= "</td>\n";
         return $out;
      }
   }

   /*static function showHeader($value) {
    
      $out ="<th class='ipdisc_tab_header'>";
            $out .= $value;
            $out .= "</th>\n";
      return $out;
   }*/


   /**
    * @param $target
    */
   static function checkBox($target)
   {

      echo "<div class='center'><a href='" . $target . "?check=all' " .
         "onclick= \"if (markCheckboxes('ipdiscover_form')) return false;\">" . __('Check all') .
         "</a>&nbsp;/&nbsp;\n";
      echo "<a href='" . $target . "?check=none' " .
         "onclick= \"if ( unMarkCheckboxes('ipdiscover_form') ) return false;\">" .
         __('Uncheck all') . "</a></div>\n";
   }

   /**
    * get the mac adresses in glpi_plugin_ocsinventoryng_ipdiscoverocslinks table
    * @global type $DB
    * @return array with mac addresses
    */

   static function getKnownMacAdresseFromGlpi()
   {
      global $DB;
      $macAdresses = array();
      $query = "SELECT `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
                FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`";
      $result = $DB->query($query);
      while ($res = $DB->fetch_assoc($result)) {
         $macAdresses[] = $res["macaddress"];
      }
      return $macAdresses;
   }


   /**
    * this function delete datas on an certain mac
    * @param type $plugin_ocsinventoryng_ocsservers_id string
    * @param type $macAdresses array
    * @return none
    */
   static function deleteMACFromOCS($plugin_ocsinventoryng_ocsservers_id, $macAdresses)
   {

      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();

      $macs = self::getMacAdressKeyVal($macAdresses);

      foreach ($macs as $key => $mac) {
         $query = " DELETE FROM `netmap` WHERE `MAC`='$mac' ";
         $DBOCS->query($query);

         $query = " DELETE FROM `network_devices` WHERE `MACADDR`='$mac' ";
         $DBOCS->query($query);
      }
   }

   /**
    * this function delete link on an certain mac
    * @param $id_links
    * @return none
    * @internal param type $plugin_ocsinventoryng_ocsservers_id string
    * @internal param type $ids array
    */
   static function deleteLink($id_links)
   {

      $ids = self::getMacAdressKeyVal($id_links);

      foreach ($ids as $key => $id) {
         $disc = new self();
         $disc->deleteByCriteria(array('id' => $id));
      }
   }


   /**
    * @param $array
    * @param $key
    * @param $val
    * @return bool
    */
   static function findInArray($array, $key, $val)
   {
      foreach ($array as $item)
         if (isset($item[$key]) && $item[$key] == $val)
            return true;
      return false;
   }

   /**
    * this function get datas on an certain ipaddress
    * @param type $ipAdress string
    * @param type $plugin_ocsinventoryng_ocsservers_id string
    * @param type $status string
    * @param array|type $knownMacAdresses array
    * @return array
    */
   static function getHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id, $status, $knownMacAdresses = array())
   {
      global $DB;

      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query = "";

      if ($status == "inventoried") {
         $query = " SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain` 
         FROM `hardware` 
         LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id` 
         WHERE `networks`.`ipsubnet`='$ipAdress' 
         AND status='Up' 
         GROUP BY `hardware`.`id`,`hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain`
         ORDER BY `hardware`.`lastdate`";
      } else if ($status == "imported") {

         $query = " SELECT *
         FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` 
         WHERE `subnet` = '$ipAdress'
         ORDER BY `last_update`";

      } else if ($status == "noninventoried") {
         $query = " SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name` as DNS
              FROM `netmap` 
              LEFT JOIN `networks` 
              ON `netmap`.`mac` =`networks`.`macaddr` 
              WHERE `netmap`.`netid`='$ipAdress' 
              AND (`networks`.`macaddr` IS NULL OR `networks`.`ipsubnet` <> `netmap`.`netid`) 
              AND `netmap`.`mac` NOT IN ( SELECT DISTINCT(`network_devices`.`macaddr`) 
              FROM `network_devices`)
              GROUP BY `netmap`.`mac`,`netmap`.`ip`,`netmap`.`mask`, `netmap`.`date`, `netmap`.`name`
              ORDER BY `netmap`.`date` DESC";
      } else {
         //group by doesn't work well
         $macAdresses = self::parseArrayToString($knownMacAdresses);
         $query = "SELECT `network_devices`.`id`,`network_devices`.`type`,`network_devices`.`description`,`network_devices`.`user`,`netmap`.`ip`,`netmap`.`mac`,`netmap`.`mask`,`netmap`.`netid`,`netmap`.`name`,`netmap`.`date`
              FROM `network_devices`
              LEFT JOIN `netmap` 
              ON `network_devices`.`macaddr`=`netmap`.`mac` 
              WHERE `netmap`.`netid`='$ipAdress'
              AND `network_devices`.`MACADDR` NOT IN($macAdresses)
              GROUP BY `network_devices`.`macaddr`,`network_devices`.`id`,`network_devices`.`type`,`network_devices`.`description`,`network_devices`.`user`,`netmap`.`ip`,`netmap`.`mac`,`netmap`.`mask`,`netmap`.`netid`,`netmap`.`name`,`netmap`.`date`
              ORDER BY `netmap`.`date` DESC";
      }
      if ($status == "imported") {
         $result = $DB->query($query);
         $hardware = array();
         while ($res = $DBOCS->fetch_assoc($result)) {
            if (!isset($res["mac"])) {
               $hardware[] = $res;
            } else if (!self::findInArray($hardware, "mac", $res["mac"])) {
               $hardware[] = $res;
            }
         }
      } else {
         $result = $DBOCS->query($query);
         $hardware = array();

         while ($res = $DBOCS->fetch_assoc($result)) {
            if (!isset($res["mac"])) {
               $hardware[] = $res;
            } else if (!self::findInArray($hardware, "mac", $res["mac"])) {
               $hardware[] = $res;
            }
         }
      }
      return $hardware;
   }

   /**
    * this function load in memory the mac address constructor
    */
   static function loadMacConstructor()
   {
      $macFile = GLPI_ROOT . "/plugins/ocsinventoryng/files/macManufacturers.txt";
      $result = "";
      $macs = array();
      if ($file = @fopen($macFile, "r")) {
         while (!feof($file)) {
            $line = fgets($file, 4096);
            if (preg_match("/^((?:[a-fA-F0-9]{2}-){2}[a-fA-F0-9]{2})\s+\(.+\)\s+(.+)\s*$/", $line, $result)) {
               $macs[mb_strtoupper(str_replace("-", ":", $result[1]))] = $result[2];

            }
         }
         $_SESSION["OCS"]["IpdiscoverMacConstructors"] = serialize($macs);
         fclose($file);
      }
   }

   /*
   static function getInventoriedComputers($ipAdress, $plugin_ocsinventoryng_ocsservers_id) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query     = " SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain` 
         FROM `hardware` 
         LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id` 
         WHERE `networks`.`ipsubnet`='$ipAdress' 
         AND status='Up' 
         ORDER BY `hardware`.`lastdate`";
      $result    = $DBOCS->query($query);
      $inv       = array();
      while ($res       = $DBOCS->fetch_assoc($result)) {
         $inv[] = $res;
      }
      return $inv;
   }

   static function getNonInventoriedHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query     = " SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name` as DNS
              FROM `netmap` 
              LEFT JOIN `networks` 
              ON `netmap`.`mac` =`networks`.`macaddr` 
              WHERE `netmap`.`netid`='$ipAdress' 
              AND (`networks`.`macaddr` IS NULL OR `networks`.`ipsubnet` <> `netmap`.`netid`) 
              AND `netmap`.`mac` NOT IN ( SELECT DISTINCT(`network_devices`.`macaddr`) 
              FROM `network_devices`) ORDER BY `netmap`.`ip`";
      $result    = $DBOCS->query($query);
      $nonInv    = array();
      while ($res       = $DBOCS->fetch_assoc($result)) {
         $nonInv[] = $res;
      }
      return $nonInv;
   }

   static function getIdentifieddHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id) {
      $ocsClient          = new PluginOcsinventoryngOcsServer();
      $DBOCS              = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query              = "SELECT `network_devices`.`ID`,`network_devices`.`TYPE`,`network_devices`.`DESCRIPTION`,`network_devices`.`USER`,`netmap`.`IP`,`netmap`.`MAC`,`netmap`.`MASK`,`netmap`.`NETID`,`netmap`.`NAME`,`netmap`.`DATE`
              FROM `network_devices`
              LEFT JOIN `netmap` 
              ON `network_devices`.`MACADDr`=`netmap`.`MAC` 
              WHERE `netmap`.`NETID`='$ipAdress' 
              ORDER BY `network_devices`.`TYPE` asc";
      $result             = $DBOCS->query($query);
      $identifiedHardware = array();
      while ($res                = $DBOCS->fetch_assoc($result)) {
         $identifiedHardware[] = $res;
      }
      return $identifiedHardware;
   }*/

   /**
    * show details on a certain subnet
    * @param type $subnetsArray array
    * @param int|type $lim integer
    * @param int $start
    * @param string $action
    * @global type $CFG_GLPI
    */
   static function showSubnetsDetails($subnetsArray, $lim = 0, $start = 0, $action = "import")
   {
      global $CFG_GLPI, $DB;
      $output_type = Search::HTML_OUTPUT; //0
      $return = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php";
      $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
      $choice = "subnetsChoice=" . $subnetsArray["subnetsChoice"] . "&action=$action";
      $subnets = $subnetsArray["subnets"];
      $row_num = 1;

      $hardwareNetwork = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php";

      echo Html::printPager($start, count($subnets), $link, $choice);
      echo Search::showNewLine($output_type, true);
      $header_num = 1;
      echo "<table width='100%'class='tab_cadrehov'>\n";
      echo Search::showHeaderItem($output_type, __('Description'), $header_num);
      echo Search::showHeaderItem($output_type, __('Subnet'), $header_num);
      echo Search::showHeaderItem($output_type, __('Non Inventoried', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Inventoried', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Identified', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Imported / Linked', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Percent done'), $header_num);
      echo Search::showEndLine($output_type);

      //limit number of displayed items
      for ($i = $start; $i < $lim + $start; $i++) {
         if (isset($subnets[$i])) {
            $row_num++;
            $item_num = 1;
            $name = "unknow";
            echo Search::showNewLine($output_type, $row_num % 2);
            if ($subnets[$i]["NAME"] != "") {
               $name = $subnets[$i]["NAME"];
            }
            echo Search::showNewLine($output_type, $row_num % 2);
            $ip = $subnets[$i]["IP"];
            $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php?ip=$ip";
            echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, $name, $item_num, $row_num) . "</a></td>";
            echo Search::showItem($output_type, $ip, $item_num, $row_num);
            $link = $hardwareNetwork . "?ip=$ip&status=noninventoried&action=$action";
            echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, $subnets[$i]["NON_INVENTORIED"], $item_num, $row_num) . "</a></td>";
            $link = $hardwareNetwork . "?ip=$ip&status=inventoried&action=$action";
            echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, $subnets[$i]["INVENTORIED"], $item_num, $row_num) . "</a></td>";
            $link = $hardwareNetwork . "?ip=$ip&status=identified&action=$action";
            echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, $subnets[$i]["IDENTIFIED"], $item_num, $row_num) . "</a></td>";
            $imported_count = 0;
            $query = "SELECT count(`id`) AS count
                        FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                        WHERE `subnet` = '$ip'";
            $result = $DB->query($query);
            if ($DB->numrows($result)) {
               $datas = $DB->fetch_assoc($result);
               $imported_count = $datas['count'];
            }
            if ($imported_count > 0) {
               $link = $hardwareNetwork . "?ip=$ip&status=imported&action=$action";
               echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, $imported_count, $item_num, $row_num) . "</a></td>";
            } else {
               echo "<td class='center'>0</td>";
            }

            echo self::showPercentBar($subnets[$i]["PERCENT"]);
         }
      }
      echo "</table>\n";
      $back = __('Back');
      echo "<div class='center'><a href='$return'>$back</div>";
   }

   /**
    * this method alows you to modify a subnet name id and mask
    * @param type $ipAdress string
    * @param array|type $values array
    * @global type $CFG_GLPI
    */

   static function modifyNetworkForm($ipAdress, $values = array())
   {
      global $CFG_GLPI;
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsdb = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $OCSDB = $ocsdb->getDB();
      $addQuery = "";
      if (!empty($values) && $values["subnetChoice"] != "0") {
         $name = $values["subnetName"];
         $id = $values["subnetChoice"];
         $mask = $values["subnetMask"];
         if (isset($_POST["Add"])) {
            $addQuery = "INSERT INTO `subnet`
              (`netid`,`name`,`id`,`mask`) 
              VALUES ('$ipAdress','$name', '$id','$mask')";
         } else if (isset($_POST["Modify"])) {

            $addQuery = "UPDATE `subnet`
              SET `name`='$name', `id`= '$id', `mask`='$mask' 
              WHERE `netid`= '$ipAdress'";
         }
         $res = $OCSDB->query($addQuery);
         if ($res) {
            $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?subnetsChoice=2"; //2 is for the known subnets
            Html::redirect($link);
         }
      } else {
         echo "<div class='center'><table class='tab_cadre' width='60%'>";
         echo "<tr class='tab_bg_2'><th colspan='4'>" . __('Modify Subnet', 'ocsinventoryng') . "</th></tr>\n";
         $query = "SELECT *
              FROM subnet
              WHERE `subnet`.`NETID` = '$ipAdress'";
         $result = $OCSDB->query($query);
         $target = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php?ip=$ipAdress\"";

         echo "<form name=\"idSelection\" action=\"" . $target . " method='post'>";
         echo "<tr class='tab_bg_2' ><td class='center' >" . __('Subnet Name', 'ocsinventoryng') . "</td>";
         //this is for the identified subnets
         if ($result->num_rows > 0) {
            $res = $OCSDB->fetch_assoc($result);
            $n = $res["NAME"];
            $m = $res["MASK"];
            $idValue = $res["ID"];
            echo "<td> <input type=\"text\" name=\"subnetName\" value=\"$n\" required></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
            echo "<td>";
            $sbnts = array(Dropdown::EMPTY_VALUE);
            self::getAllSubnetsID($sbnts);
            Dropdown::showFromArray('subnetChoice', $sbnts, array('value' => $idValue));
            echo "</td>";
            echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";

            echo "<td class=''>" . $ipAdress . "</td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Subnet mask') . "</td>";
            echo "<td><input type=\"text\" name=\"SubnetMask\" value=\"$m\" required></td></tr>";
            echo "<tr class='tab_bg_2' ><td class='center'><input type='submit' name='Modify' value=\"" . _sx('button', 'Update') . "\" class='submit'></td>";
            Html::closeForm();
            echo "<form name=\"idSelection\" action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?ip=?$ipAdress&ident=2\" method='post'>";
            echo "<td class='center'> <input type='submit' name='Cancel' value=\"" . _sx('button', 'Cancel') . "\" class='submit'></td></tr></div>";
            Html::closeForm();
         } //this is for the unidentified subnets
         else {
            echo "<td> <input type=\"text\" name=\"subnetName\" value=\"\" required></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
            echo "<td>";
            $sbnts = array(Dropdown::EMPTY_VALUE);
            self::getAllSubnetsID($sbnts);
            Dropdown::showFromArray('subnetChoice', $sbnts, array('on_change' => 'FillInput();'));
            echo "</td>";

            echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";
            echo "<td class=''>" . $ipAdress . "</td></tr>";
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>" . __('Subnet mask') . "</td>";
            echo "<td> <input type=\"text\" name=\"SubnetMask\" value=\"\" required></td></tr>";
            echo "<tr class='tab_bg_2' ><td class='center'><input type='submit' name='Add' value=\"" . _sx('button', 'Add') . "\" class='submit'></td>";
            Html::closeForm();
            echo "<form name=\"idSelection\" action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?ip=?$ipAdress&nonident=3\" method='post'>";
            echo "<td class='center'> <input type='submit' name='Cancel' value=\"" . _sx('button', 'Cancel') . "\" class='submit'></td></tr></div>";
            Html::closeForm();
         }

      }
      echo "</table></div><br>\n";

   }

   /**
    * check if ipdiscover object must be updated or imported
    * @param type $ipDiscoveryObject array
    * @param type $plugin_ocsinventoryng_ocsservers_id integer
    * @param $subnet
    * @return type array with the status of the import or update process
    * @global type $DB
    */
   static function processIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet)
   {
      global $DB;
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsClient->checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $mac = $ipDiscoveryObject["macAdress"];
      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                WHERE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
                LIKE '$mac' 
                AND `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`plugin_ocsinventoryng_ocsservers_id` ='$plugin_ocsinventoryng_ocsservers_id'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         $datas = $DB->fetch_assoc($result);
         return self::updateIpDiscover($ipDiscoveryObject, $datas, $plugin_ocsinventoryng_ocsservers_id);
      }

      return self::importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet);
   }

   /**
    * import ipdiscover object
    * @param type $ipDiscoveryObject array
    * @param type $plugin_ocsinventoryng_ocsservers_id integer
    * @param $subnet
    * @return array
    */
   static function importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet)
   {
      global $DB;

      $id = null;
      $identify = false;
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      if (isset($ipDiscoveryObject["ocsItemType"])
         && $ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE
      ) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }
      if ($ipDiscoveryObject["itemDescription"] == '') {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }

      if ($ipDiscoveryObject["itemName"] == "") {
         $ipDiscoveryObject["itemName"] = $ipDiscoveryObject["itemDescription"];
      }

      switch ($ipDiscoveryObject["glpiItemType"]) {
         //empty dropdown value
         case '0' :
            return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }

      if (isset($ipDiscoveryObject["ocsItemType"])) {
         $identify = true;
      }

      $mac = $ipDiscoveryObject["macAdress"];
      $netPort = new NetworkPort();
      $netPort->getFromDBByQuery("WHERE `mac` = '$mac' ");
      if (count($netPort->fields) < 1) {

         $input = array(
            'is_dynamic' => 1,
            'locations_id' => 0,
            'domains_id' => 0,
            'entities_id' => $ipDiscoveryObject["entity"],
            'name' => $ipDiscoveryObject["itemName"],
            'comment' => $ipDiscoveryObject["itemDescription"]);

         $device = new $ipDiscoveryObject["glpiItemType"]();

         $id = $device->add($input, array(), $cfg_ocs['history_devices']);

         //ipdiscover link
         $date = date("Y-m-d H:i:s");
         $glpiType = $ipDiscoveryObject["glpiItemType"];
         $ip = $ipDiscoveryObject["itemIp"];

         $glpiQuery = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                       (`items_id`,`itemtype`,`macaddress`,`last_update`,`subnet`,`plugin_ocsinventoryng_ocsservers_id`)
                       VALUES('$id','$glpiType','$mac','$date','$subnet','$plugin_ocsinventoryng_ocsservers_id')";
         $DB->query($glpiQuery);

         //add port
         $port_input = array('name' => $ipDiscoveryObject["itemName"] . "-" . $ip,
            'mac' => $mac,
            'items_id' => $id,
            'itemtype' => $glpiType,
            'instantiation_type' => "NetworkPortEthernet",
            "entities_id" => $ipDiscoveryObject["entity"],
            "NetworkName__ipaddresses" => array("-100" => $ip),
            '_create_children' => 1,
            //'is_dynamic'                => 1,
            'is_deleted' => 0);

         $netPort->add($port_input, array(), $cfg_ocs['history_network']);

         /* $netPortInput = array(
              "itemtype"           => $glpiType,
              "items_id"           => $id,
              'entities_id'        => $ipDiscoveryObject["entity"],
              "name"               => $ipDiscoveryObject["itemName"] . "-" . $ip,
              "instantiation_type" => "NetworkPortEthernet",
              "mac"                => $mac
          );

          $netPort->splitInputForElements($netPortInput);
          $NewNetPortId = $netPort->add($netPortInput);
          $netPort->updateDependencies(1);
          //make link to IPAdress manualy

          //add ipAdress
          $networkName= new NetworkName();
          $networkNameId=$networkName->add(array("items_id"=>$NewNetPortId,"itemtype"=>"NetworkPort"));
          $ipAdresses = new IPAddress();

          $input = array('name'        => $ip,
                      'itemtype'    => 'NetworkName',
                      'items_id'    => $networkNameId,
                      'is_deleted'  => 0,
                       'mainitems_id'=>$id,
                       'mainitemtype'=>$glpiType);
          $ipAdresses->add($input);*/
      }

      if ($id && $identify) {
         //identify object
         //WAS IS DAS ? CHMA
         $userId = Session::getLoginUserID();
         $query = "SELECT `glpi_users`.`name` 
                         FROM `glpi_users`
                         WHERE glpi_users.id like '$userId'";
         $queryResult = $DB->query($query);
         $userAssoc = $DB->fetch_assoc($queryResult);

         if ($userAssoc) {
            $ocsClient = new PluginOcsinventoryngOcsServer();
            $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
            $ocsType = $ipDiscoveryObject["ocsItemType"];
            $description = $ipDiscoveryObject["itemDescription"];
            $user = $userAssoc["name"];
            $ocsQuery = "INSERT INTO `network_devices` (`description`,`type`,`macaddr`,`user`)
                            VALUES('$description','$ocsType','$mac','$user')";
            $DBOCS->query($ocsQuery);
         }
      }

      if ($id) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_IMPORTED);
      } else {
         Session::addMessageAfterRedirect($mac . " : " . __('Unable to add. an object with same MAC address already exists.', 'ocsinventoryng'), false, ERROR);
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }
   }


   /**
    * link ipdiscover object
    * @param type $plugin_ocsinventoryng_ocsservers_id integer
    * @param $itemtypes
    * @param $items_id
    * @param $macAdresses
    * @param $ocsItemstypes
    * @param $itemsDescription
    * @param $subnet
    * @param int $identify
    * @return type array
    * @internal param type $ipDiscoveryObject array
    */
   static function linkIpDiscover($plugin_ocsinventoryng_ocsservers_id, $itemtypes, $items_id, $macAdresses, $ocsItemstypes, $itemsDescription, $subnet, $identify = 0)
   {
      global $DB;

      /*$id         = null;
      $identify   = false;
      
      if (isset($ipDiscoveryObject["ocsItemType"]) 
            && $ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }
      if ($ipDiscoveryObject["itemDescription"] == '') {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }

      if ($ipDiscoveryObject["itemName"] == "") {
         $ipDiscoveryObject["itemName"] = $ipDiscoveryObject["itemDescription"];
      }

      switch ($ipDiscoveryObject["glpiItemType"]) {
         //empty dropdown value
         case '0' :
            return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }

      if (isset($ipDiscoveryObject["ocsItemType"])) {
         $identify = true;
      }*/

      //ipdiscover link
      $objs = array();
      $macs = self::getMacAdressKeyVal($macAdresses);

      foreach ($itemtypes as $key => $type) {
         $objs[$items_id[$key]] = array("itemtype" => $type,
            "mac" => $macs[$key],
            "description" => (isset($itemsDescription[$key]) ? $itemsDescription[$key] : 0),
            "ocsType" => (isset($ocsItemstypes[$key]) ? $ocsItemstypes[$key] : 0));
      }

      foreach ($objs as $id => $tab) {
         $date = date("Y-m-d H:i:s");

         $glpiQuery = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                       (`items_id`,`itemtype`,`macaddress`,`last_update`,`subnet`,`plugin_ocsinventoryng_ocsservers_id`)
                       VALUES('$id','" . $tab["itemtype"] . "','" . $tab["mac"] . "','$date','$subnet','$plugin_ocsinventoryng_ocsservers_id')";
         $DB->query($glpiQuery);

         $input = array(
            'is_dynamic' => 1,
            'id' => $id);

         $device = new $tab["itemtype"]();
         $device->update($input);

         if ($identify) {
            //identify object
            //WAS IS DAS ? CHMA
            $userId = Session::getLoginUserID();
            $query = "SELECT `glpi_users`.`name` 
                         FROM `glpi_users`
                         WHERE glpi_users.id like '$userId'";
            $queryResult = $DB->query($query);
            $userAssoc = $DB->fetch_assoc($queryResult);

            if ($userAssoc) {
               $ocsClient = new PluginOcsinventoryngOcsServer();
               $DBOCS = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
               $ocsType = $tab["ocsType"];
               $description = $tab["description"];
               $user = $userAssoc["name"];
               $ocsQuery = "INSERT INTO `network_devices` (`description`,`type`,`macaddr`,`user`)
                            VALUES('$description','$ocsType','" . $tab["mac"] . "','$user')";
               $DBOCS->query($ocsQuery);
            }
         }
      }


      /*if ($id) {
          return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_IMPORTED);
       } else {
          Session::addMessageAfterRedirect($mac." : ".__('Unable to add. an object with same MAC address already exists.', 'ocsinventoryng'), false, ERROR);
          return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
       }*/
   }


   /**
    * update ipdiscover object
    * @global type $DB
    * @param type $ipDiscoveryObject array
    * @param type $datas array
    * @param type $plugin_ocsinventoryng_ocsservers_id integer
    * @return array
    */
   static function updateIpDiscover($ipDiscoveryObject, $datas, $plugin_ocsinventoryng_ocsservers_id)
   {
      global $DB;
      $res = null;

      if (isset($ipDiscoveryObject["ocsItemType"]) && $ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_NOTUPDATED);
      }
      if ($ipDiscoveryObject["itemDescription"] == '') {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_NOTUPDATED);
      }

      if ($ipDiscoveryObject["itemName"] == "") {
         $ipDiscoveryObject["itemName"] = $ipDiscoveryObject["itemDescription"];
      }

      switch ($ipDiscoveryObject["glpiItemType"]) {
         //empty dropdown value
         case '0' :
            return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_NOTUPDATED);
      }

      $itemType1 = new $ipDiscoveryObject["glpiItemType"]();
      $itemType2 = new $datas["itemtype"]();

      //same type of object
      //simple data update
      if ($itemType1 == $itemType2) {
         $input = array("id" => $datas["id"],
            'entities_id' => $ipDiscoveryObject["entity"],
            'name' => $ipDiscoveryObject["itemName"],
            'comment' => $ipDiscoveryObject["itemDescription"]);
         $res = $itemType1->update($input);
         $date = date("Y-m-d H:i:s");
         $glpiQuery = "UPDATE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                         SET `last_update` = '$date'";
         $DB->query($glpiQuery);
      }
      //not same type 
      //delete old object and create a new one
      else {
         //delete old object
         $id = $datas["items_id"];
         $mac = $datas["macaddress"];
         $result = $itemType2->delete(array("id" => $id));

         if ($result) {
            //delete ipdiscoverocslink
            $glpiQuery = "DELETE FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` 
                          WHERE `macaddress` = '$mac'";
            $DB->query($glpiQuery);
            //add new ipdiscover object
            $action = self::importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id);
            if ($action["status"] == 15) {
               return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_SYNCHRONIZED);
            }
         }
      }
      if ($res) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_SYNCHRONIZED);
      } else {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_NOTUPDATED);
      }
   }

   /**
    * get all the ipdiscover objects to be imported or updated
    * @param type $macAdresses array
    * @param array|type $entities array
    * @param type $glpiItemsTypes array
    * @param string|type $itemsNames array
    * @param type $itemsDescription array
    * @param type $itempsIp array
    * @param array|type $ocsItemsTypes array
    * @return array
    */

   static function getIpDiscoverobject($macAdresses, $entities = array(), $glpiItemsTypes, $itemsNames = "", $itemsDescription, $itempsIp, $ocsItemsTypes = array())
   {
      $objectToImport = array();
      $macs = self::getMacAdressKeyVal($macAdresses);
      if (!empty($entities)) {
         foreach ($macs as $key => $mac) {
            if (!empty($ocsItemsTypes)) {
               $objectToImport[] = array("macAdress" => $mac,
                  "entity" => $entities[$key],
                  "glpiItemType" => $glpiItemsTypes[$key],
                  "ocsItemType" => $ocsItemsTypes[$key],
                  "itemName" => $itemsNames[$key],
                  "itemDescription" => $itemsDescription[$key],
                  "itemIp" => $itempsIp[$key]);
            } else {
               $objectToImport[] = array("macAdress" => $mac,
                  "entity" => $entities[$key],
                  "glpiItemType" => $glpiItemsTypes[$key],
                  "itemName" => $itemsNames[$key],
                  "itemDescription" => $itemsDescription[$key],
                  "itemIp" => $itempsIp[$key]);
            }
         }
      } else {
         foreach ($macs as $key => $mac) {
            $ent = null;
            foreach ($_SESSION["glpiactiveentities"] as $e => $eval) {
               $ent = $eval;
            }
            if (!empty($ocsItemsTypes)) {
               $objectToImport[] = array("macAdress" => $mac,
                  "entity" => $ent,
                  "glpiItemType" => $glpiItemsTypes[$key],
                  "ocsItemType" => $ocsItemsTypes[$key],
                  "itemName" => $itemsNames[$key],
                  "itemDescription" => $itemsDescription[$key],
                  "itemIp" => $itempsIp[$key]);
            } else {
               $objectToImport[] = array("macAdress" => $mac,
                  "entity" => $ent,
                  "glpiItemType" => $glpiItemsTypes[$key],
                  "itemName" => $itemsNames[$key],
                  "itemDescription" => $itemsDescription[$key],
                  "itemIp" => $itempsIp[$key]);
            }
         }
      }
      return $objectToImport;
   }

   /**
    * @param $status
    * @return string
    */
   static function showPercentBar($status)
   {
      if (!is_numeric($status)) {
         return $status;
      }
      if (($status < 0) or ($status > 100)) {
         return $status;
      }
      return "<td><div class='percent_bar'><!--" . str_pad($status, 3, "0", STR_PAD_LEFT) . "-->
             <div class='percent_status' style='width:" . $status . "px;'>&nbsp;</div>
             <div class='percent_text'>" . $status . "%</div>
             </div></td>";
   }

   /**
    * show  hardware to be identified, or identified and imported, or just the hardware with agents installed on them
    * @param type $hardware array
    * @param type $lim integer
    * @param int|type $start integer
    * @param type $ipAdress string
    * @param type $status string
    * @param $subnet
    * @param $action
    * @global type $CFG_GLPI
    */
   static function showHardware($hardware, $lim, $start = 0, $ipAdress, $status, $subnet, $action)
   {
      global $CFG_GLPI, $DB;

      $output_type = Search::HTML_OUTPUT; //0
      $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php";
      $return = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
      $returnargs = "subnetsChoice=$subnet&action=$action";
      $reload = "ip=$ipAdress&status=$status&action=$action";
      $backValues = "?b[]=$ipAdress&b[]=$status";

      if ($status == "inventoried") {
         $status_name = __('Inventoried', 'ocsinventoryng');
      } elseif ($status == "imported") {
         $status_name = __('Imported / Linked', 'ocsinventoryng');
      } elseif ($status == "noninventoried") {
         $status_name = __('Non Inventoried', 'ocsinventoryng');
      } else {
         $status_name = __('Identified', 'ocsinventoryng');
      }

      $subnet_name = self::getSubnetNamebyIP($ipAdress);
      echo "<div class='center'>";
      echo "<h2>" . __('Subnet', 'ocsinventoryng') . " " . $subnet_name . " (" . $ipAdress . ") - " . $status_name;
      echo "&nbsp;";
      $refresh = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php?" . $reload;
      Html::showSimpleForm($refresh, 'refresh', _sx('button', 'Refresh'), array(), $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/synchro.png");
      echo "</h2>";
      echo "</div>";

      if ($subnet >= 0) {
         $back = __('Back');
         echo "<div class='center'><a href='$return?$returnargs'>$back</div>";
      }

      echo Html::printPager($start, count($hardware), $link, $reload);
      echo Search::showNewLine($output_type, true);
      if (empty($hardware)) {
         echo "<div class='center b'><br>" . __('No new IPDiscover device to import', 'ocsinventoryng') . "</div>";
         Html::displayBackLink();
      } else {
         $header_num = 1;
         switch ($status) {
            case "inventoried" :
               echo "<table width='100%'class='tab_cadrehov'>\n";
               echo Search::showHeaderItem($output_type, __('User'), $header_num);
               echo Search::showHeaderItem($output_type, __('Name'), $header_num);
               echo Search::showHeaderItem($output_type, __('System'), $header_num);
               echo Search::showHeaderItem($output_type, __('Version of the operating system'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;

               for ($i = $start; $i < $lim + $start; $i++) {
                  if (isset($hardware[$i])) {
                     $row_num++;
                     $item_num = 1;

                     echo Search::showNewLine($output_type, $row_num % 2);
                     echo Search::showItem($output_type, $hardware[$i]["userid"], $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["name"], $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["osname"], $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["osversion"], $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["ipaddr"], $item_num, $row_num);
                     echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["lastdate"]), $item_num, $row_num);
                     echo Search::showEndLine($output_type);
                  }
               }
               echo "</table>\n";
               break;

            case "imported" :
               $target = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php" . $backValues;
               self::checkBox($target);
               echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
               echo "<div class='center' style=\"width=100%\">";
               echo "<input type='submit' class='submit' name='deletelink'  value=\"" . _sx('button', 'Delete link', 'ocsinventoryng') . "\"></div>";
               echo "<table width='100%'class='tab_cadrehov'>\n";
               echo Search::showHeaderItem($output_type, __('Item'), $header_num);
               echo Search::showHeaderItem($output_type, __('Item type'), $header_num);
               echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Location'), $header_num);
               echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('Subnet'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               for ($i = $start; $i < $lim + $start; $i++) {
                  if (isset($hardware[$i])) {
                     $row_num++;
                     $item_num = 1;
                     echo Search::showNewLine($output_type, $row_num % 2);

                     $class = getItemForItemtype($hardware[$i]["itemtype"]);
                     $class->getFromDB($hardware[$i]["items_id"]);
                     $iplist = "";
                     $ip = new IPAddress();
                     // Update IPAddress
                     foreach ($DB->request('glpi_networkports', array('itemtype' => $hardware[$i]["itemtype"],
                        'items_id' => $hardware[$i]["items_id"])) as $netname) {
                        foreach ($DB->request('glpi_networknames', array('itemtype' => 'NetworkPort',
                           'items_id' => $netname['id'])) as $dataname) {
                           foreach ($DB->request('glpi_ipaddresses', array('itemtype' => 'NetworkName',
                              'items_id' => $dataname['id'])) as $data) {
                              $ip->getFromDB($data['id']);
                              $iplist .= $ip->getName() . "<br>";
                           }
                        }
                     }
                     echo Search::showItem($output_type, $class->getLink(), $item_num, $row_num);
                     echo Search::showItem($output_type, $class->getTypeName(), $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["macaddress"], $item_num, $row_num);
                     echo Search::showItem($output_type, $iplist, $item_num, $row_num);
                     echo Search::showItem($output_type, Dropdown::getDropdownName("glpi_locations", $class->fields["locations_id"]), $item_num, $row_num);
                     echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["last_update"]), $item_num, $row_num);
                     echo Search::showItem($output_type, $hardware[$i]["subnet"], $item_num, $row_num);
                     echo self::showItem($hardware[$i]["id"], "", "", "", true, "", $i);
                     echo Search::showEndLine($output_type);
                  }
               }

               echo "<tbody style=\"display:none\">";
               echo "<tr><td><input type=\"hidden\" name='subnet' value=\"$ipAdress\" ></td></tr>";
               echo "</tbody>";
               echo "</table>\n";
               echo "<div class='center' style=\"width=100%\">";
               echo "<input type='submit' class='submit' name='deletelink'  value=\"" . _sx('button', 'Delete link', 'ocsinventoryng') . "\"></div>";
               Html::closeForm();
               self::checkBox($target);
               break;

            case "noninventoried" :
               $ocsTypes = array("id" => array(Dropdown::EMPTY_VALUE), "name" => array(Dropdown::EMPTY_VALUE));
               $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
               $target = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php" . $backValues;
               $macConstructor = "";
               self::getOCSTypes($ocsTypes);
               self::checkBox($target);
               echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
               echo "<div class='center' style=\"width=100%\">";
               if ($action == "import") {
                  echo "<input type='submit' class='submit' name='IdentifyAndImport'  value=\"" . _sx('button', 'Import') . "\">";
                  echo "&nbsp;";
               } else {
                  echo "<input type='submit' class='submit' name='IdentifyAndLink'  value=\"" . _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  echo "&nbsp;";
               }
               echo "<input type='submit' class='submit' name='delete'  value=\"" . _sx('button', 'Delete from OCSNG', 'ocsinventoryng') . "\"></div>";
               echo "<table width='100%'class='tab_cadrehov'>\n";
               echo Search::showHeaderItem($output_type, __('Date'), $header_num);
               echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Subnet mask'), $header_num);
               echo Search::showHeaderItem($output_type, __('DNS', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('Description') . "<span class='red'>*</span>", $header_num);
               echo Search::showHeaderItem($output_type, __('OCS Type', 'ocsinventoryng') . "<span class='red'>*</span>", $header_num);
               if ($action == "import") {
                  echo Search::showHeaderItem($output_type, __('Name'), $header_num);
                  if (Session::isMultiEntitiesMode()) {
                     echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
                  }

                  echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng') . "<span class='red'>*</span>", $header_num);
               } else {
                  echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
               }
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               $ocstypes = array();
               foreach ($ocsTypes["name"] as $items) {
                  $ocstypes[$items] = $items;
               }
               $itemstypes = array(Dropdown::EMPTY_VALUE);
               foreach (self::$hardwareItemTypes as $items) {
                  $class = getItemForItemtype($items);
                  $itemstypes[$items] = $class->getTypeName();
               }
               for ($i = $start; $i < $lim + $start; $i++) {
                  if (isset($hardware[$i])) {
                     $row_num++;
                     echo Search::showNewLine($output_type, $row_num % 2);
                     echo self::showItem(Html::convDateTime($hardware[$i]["date"]));
                     if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                        $macs = unserialize($_SESSION["OCS"]["IpdiscoverMacConstructors"]);
                        if (isset($macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                           $macConstructor = $macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                        } else {
                           $macConstructor = __("unknow");
                        }
                     }

                     $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                     echo self::showItem($mac);
                     echo self::showItem($ip = $hardware[$i]["ip"]);
                     echo self::showItem($hardware[$i]["mask"]);

                     echo self::showItem($hardware[$i]["DNS"]);
                     echo "<td><input type=\"text\" name='itemsdescription[" . $i . "]' value=\"\" ></td>";
                     echo "<td>";
                     Dropdown::showFromArray("ocsitemstype[$i]", $ocstypes);
                     echo "</td>";

                     if ($action == "import") {
                        echo "<td><input type=\"text\" name='itemsname[" . $i . "]' value=\"\"></td>";
                        if (Session::isMultiEntitiesMode()) {
                           echo "<td>";
                           Entity::dropdown(array('name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]));
                           echo "</td>";
                        }

                        echo "<td>";
                        Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                        echo "</td>";
                     } else {
                        echo "<td width='10'>";

                        $mtrand = mt_rand();

                        $mynamei = "itemtype";
                        $myname = "tolink_items[" . $i . "]";

                        $rand = Dropdown::showItemTypes($mynamei, self::$hardwareItemTypes, array('rand' => $mtrand));


                        $p = array('itemtype' => '__VALUE__',
                           'entity_restrict' => $_SESSION["glpiactiveentities"],
                           'id' => $i,
                           'rand' => $rand,
                           'myname' => $myname);
                        //print_r($p);
                        Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/ajax/dropdownitems.php", $p);
                        echo "<span id='results_$mynamei$rand'>\n";
                        echo "</span>\n";
                        //}
                        echo "</td>";
                     }
                     echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
                     echo "<tbody style=\"display:none\">";
                     echo "<tr><td><input type=\"hidden\" name='itemsip[" . $i . "]' value=\"$ip\" >
                           <input type=\"hidden\" name='subnet' value=\"$ipAdress\" ></td></tr>";
                     echo "</tbody>";
                  }
               }
               echo "</table>\n";
               echo "<div class='center' style=\"width=100%\">";
               if ($action == "import") {
                  echo "<input type='submit' class='submit' name='IdentifyAndImport'  value=\"" . _sx('button', 'Import') . "\">";
                  echo "&nbsp;";
               } else {
                  echo "<input type='submit' class='submit' name='IdentifyAndLink'  value=\"" . _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  echo "&nbsp;";
               }
               echo "<input type='submit' class='submit' name='delete'  value=\"" . _sx('button', 'Delete from OCSNG', 'ocsinventoryng') . "\"></div>";
               Html::closeForm();
               self::checkBox($target);
               break;

            default :
               $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
               $target = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php" . $backValues;
               $macConstructor = "";
               self::checkBox($target);
               echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
               echo "<div class='center' style=\"width=100%\">";
               if ($action == "import") {
                  echo "<input type='submit' class='submit' name='Import'  value=\"" . _sx('button', 'Import') . "\">";
                  echo "&nbsp;";
               } else {
                  echo "<input type='submit' class='submit' name='Link'  value=\"" . _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  echo "&nbsp;";
               }
               echo "<input type='submit' class='submit' name='delete'  value=\"" . _sx('button', 'Delete from OCSNG', 'ocsinventoryng') . "\"></div>";
               echo "<table width='100%'class='tab_cadrehov'>";
               echo Search::showHeaderItem($output_type, __('Date'), $header_num);
               echo Search::showHeaderItem($output_type, __('Description'), $header_num);
               echo Search::showHeaderItem($output_type, __('OCS Type', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);

               if ($action == "import") {
                  if (Session::isMultiEntitiesMode()) {
                     echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
                  }
                  echo Search::showHeaderItem($output_type, __('Name'), $header_num);

                  echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng') . "<span class='red'>*</span>", $header_num);
               } else {
                  echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
               }
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               $itemstypes = array(Dropdown::EMPTY_VALUE);
               foreach (self::$hardwareItemTypes as $items) {
                  $class = getItemForItemtype($items);
                  $itemstypes[$items] = $class->getTypeName();
               }
               for ($i = $start; $i < $lim + $start; $i++) {
                  if (isset($hardware[$i])) {
                     $row_num++;
                     echo Search::showNewLine($output_type, $row_num % 2);
                     echo self::showItem(Html::convDateTime($hardware[$i]["date"]));
                     echo self::showItem($description = $hardware[$i]["description"]);
                     echo self::showItem($hardware[$i]["type"]);
                     echo self::showItem($ip = $hardware[$i]["ip"]);
                     if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                        $macs = unserialize($_SESSION["OCS"]["IpdiscoverMacConstructors"]);
                        if (isset($macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                           $macConstructor = $macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                        } else {
                           $macConstructor = __("unknow");
                        }
                     }

                     $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                     echo self::showItem($mac);

                     if ($action == "import") {
                        if (Session::isMultiEntitiesMode()) {
                           echo "<td>";
                           Entity::dropdown(array('name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]));
                           echo "</td>";
                        }

                        echo "<td><input type=\"text\" name='itemsname[" . $i . "]' value=\"\"></td>";
                        echo "<td>";
                        Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                        echo "</td>";
                     } else {
                        echo "<td width='10'>";

                        $mtrand = mt_rand();

                        $mynamei = "itemtype";
                        $myname = "tolink_items[" . $i . "]";

                        $rand = Dropdown::showItemTypes($mynamei, self::$hardwareItemTypes, array('rand' => $mtrand));


                        $p = array('itemtype' => '__VALUE__',
                           'entity_restrict' => $_SESSION["glpiactiveentities"],
                           'id' => $i,
                           'rand' => $rand,
                           'myname' => $myname);

                        Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/ajax/dropdownitems.php", $p);
                        echo "<span id='results_$mynamei$rand'>\n";
                        echo "</span>\n";

                        echo "</td>";
                     }
                     echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
                     echo "<tbody style=\"display:none\">";
                     echo "<tr><td><input type=\"hidden\" name='itemsip[" . $i . "]' value=\"$ip\" >";
                     echo "<input type=\"hidden\" name='itemsdescription[" . $i . "]' value=\"$description\" >
                             <input type=\"hidden\" name='subnet' value=\"$ipAdress\" ></tr>";
                     echo "</td></tr></tbody>";
                  }
               }
               echo "</table>";
               echo "<div class='center' style=\"width=100%\">";
               if ($action == "import") {
                  echo "<input type='submit' class='submit' name='Import'  value=\"" . _sx('button', 'Import') . "\">";
                  echo "&nbsp;";
               } else {
                  echo "<input type='submit' class='submit' name='Link'  value=\"" . _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  echo "&nbsp;";
               }
               echo "<input type='submit' class='submit' name='delete'  value=\"" . _sx('button', 'Delete from OCSNG', 'ocsinventoryng') . "\"></div>";
               Html::closeForm();
               self::checkBox($target);
               break;
         }
      }
      if ($subnet >= 0) {
         $back = __('Back');
         echo "<div class='center'><a href='$return?$returnargs'>$back</div>";
      }
   }


   /**
    * get the key(position) of the macaddress
    * @param $macAdresses
    * @return array with the keys(positions)
    */
   static function getMacAdressKeyVal($macAdresses)
   {
      $keys = array();
      foreach ($macAdresses as $key => $val) {
         foreach ($val as $mac => $on) {
            $keys[$key] = $mac;
         }
      }
      return $keys;
   }

}