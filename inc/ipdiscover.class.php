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

//class PluginOcsinventoryngIpDiscover extends CommonDBTM{
class PluginOcsinventoryngIpDiscover extends CommonGLPI {

   static $hardwareItemTypes = array('Computer', 'Network device', 'Device', 'Phone', 'Printer');
   static protected $notable = false;

   //public $taborientation          = 'vertical';
   //var $fields                              = array();//need it for Search::show('PluginOcsinventoryngIpDiscover');
   
   /*const IPDISCOVER_SYNCHRONIZED =15;
   const IPDISCOVER_IMPORTED =16;
   const IPDISCOVER_FAILED_IMPORT =17;
   const IPDISCOVER_LINKED =18;
   const IPDISCOVER_NOTUPDATED =19;*/
            

   static function getTypeName($nb = 0) {
      return _n('OCS Inventory NG ', 'OCS Inventorys NG', $nb, 'ocsinventoryng');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case __CLASS__ :
            $ong[0]    = __('Server OCS Inventory NG', 'ocsinventoryng');
            $ocsClient = new PluginOcsinventoryngOcsServer();
            $client    = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
            $version   = $client->getTextConfig('GUI_VERSION');
            $snmp      = $client->getIntConfig('SNMP');
            if ($version > $ocsClient::OCS2_1_VERSION_LIMIT && $snmp) {
               $ong[1] = __('SNMP', 'ocsinventoryng');
            }
            $ong[2] = __('IPDISCOVER', 'ocsinventoryng');
            return $ong;

         default :
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 0, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $ocs = new PluginOcsinventoryngOcsServer();
         switch ($tabnum) {
            case 0 :
               $ocs->ocsMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;

            case 1 :
               $ocs->snmpMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;

            case 2 :
               $item->ipDiscoverMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;
         }
      }
      return true;
   }

   function defineTabs($options = array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }

   /**
    * parse array with ip or mac into one string
    * @param type $array
    * @return string$
    */
   public static function parseArrayToString($array = array()) {
      $token = "";
      if (sizeof($array) == 0) {
         return "''";
      }
      for ($i = 0; $i < sizeof($array); $i++) {
         if ($i == sizeof($array) - 1) {
            $token .="'" . $array[$i] . "'";
         } else {
            $token .="'" . $array[$i] . "'" . ",";
         }
      }
      return $token;
   }

   public static function getSubnetsName($inputs, &$outputs) {

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
    * 
    */
   public static function getAllSubnetsID(&$outputs) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query     = "SELECT `config`.`TVALUE`
              FROM `config` 
              WHERE 
              `config`.`NAME` 
              LIKE 'ID_IPDISCOVER_%'";
      $result    = $DBOCS->query($query);
      while ($subNetId  = $DBOCS->fetch_assoc($result)) {
         $outputs[$subNetId["TVALUE"]] = $subNetId["TVALUE"];
      }
   }

   public static function getSubnetsID($inputs, &$outputs) {
      foreach ($inputs as $subnets) {
         if (isset($subnets["ID"])) {
            if ($subnets["ID"] != null && !in_array($subnets["ID"], $outputs)) {
               $outputs[] = $subnets["ID"];
            }
         }
      }
   }

   public static function countSubnetsID(&$count) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query     = "SELECT MAX(`config`.`IVALUE`) as MAX
              FROM `config` 
              WHERE 
              `config`.`NAME` 
              LIKE 'ID_IPDISCOVER_%'";
      $result    = $DBOCS->query($query);
      $subNetId  = $DBOCS->fetch_assoc($result);
      $count     = intval($subNetId["MAX"]);
   }

   public static function getEntities(&$out) {
      global $DB;
      $query  = "SELECT `glpi_entities`.`id` , `glpi_entities`.`name` , `glpi_entities`.`entities_id`
             FROM `glpi_entities`";
      $result = $DB->query($query);

      while ($ent = $DB->fetch_assoc($result)) {
         $out["id"][]          = $ent["id"];
         $out["name"][]        = $ent["name"];
         $out["entities_id"][] = $ent["entities_id"];
      }
   }

   public static function getOCSTypes(&$out) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query     = "SELECT `devicetype`.`id` , `devicetype`.`name`
             FROM `devicetype`";
      $result    = $DBOCS->query($query);

      while ($ent = $DBOCS->fetch_assoc($result)) {
         $out["id"][]   = $ent["id"];
         $out["name"][] = $ent["name"];
      }
   }

   public static function getSubnets($plugin_ocsinventoryng_ocsservers_id) {
      $subnets   = array();
      $unknown   = array();
      $known     = array();
      $unknownIP = array();
      $knownIP   = array();
      $IP        = array();
      $query     = "SELECT DISTINCT `NETWORKS`.`IPSUBNET`,`SUBNET`.`NAME`,`SUBNET`.`ID`
               FROM `NETWORKS` 
               LEFT JOIN `SUBNET` 
               ON (`NETWORKS`.`IPSUBNET` = `SUBNET`.`NETID`) ,`ACCOUNTINFO`
               WHERE `NETWORKS`.`HARDWARE_ID`=`ACCOUNTINFO`.`HARDWARE_ID`
               AND `NETWORKS`.`STATUS`='Up'";
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $result    = $DBOCS->query($query);
      while ($subNet    = $DBOCS->fetch_assoc($result)) {
         $subnets[] = $subNet;
      }

      for ($i = 0; $i < count($subnets); $i++) {
         if ($subnets[$i]["NAME"] == null) {
            $unknown[]   = $subnets[$i];
            $unknownIP[] = $subnets[$i]["IPSUBNET"];
         }
      }
      for ($i = 0; $i < count($subnets); $i++) {
         if ($subnets[$i]["NAME"] != null) {
            $known[]   = $subnets[$i];
            $knownIP[] = $subnets[$i]["IPSUBNET"];
         }
      }
      return array("All Subnets" => $subnets, "Known Subnets" => $known, "Unknown Subnets" => $unknown, "knownIP" => $knownIP, "unknownIP" => $unknownIP);
   }

   public static function showSubnets($plugin_ocsinventoryng_ocsservers_id, $subnets, $knownMacAdresses, $option = "") {
      //this query displays all the elements on the the networks we found :
      $subnetsDetails = array();
      $knownNets      = "";
      $unknownNets    = "";
      $Nets           = "";
      $ocsClient      = new PluginOcsinventoryngOcsServer();
      $DBOCS          = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      if ($option == "Known Subnets") {

         $knownNets = self::parseArrayToString($subnets["knownIP"]);
         $Nets      = $knownNets;
      } else if ($option == "Unknown Subnets") {
         $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
         $Nets        = $unknownNets;
      } else if ($option == "All Subnets") {
         $knownNets   = self::parseArrayToString($subnets["knownIP"]);
         $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
         $Nets        = $knownNets . "," . $unknownNets;
      } else {
         if ($option != "") {
            $theSubnet      = array();
            $getKnownSubnet = "SELECT `subnet`.`NETID`
                          FROM `subnet`
                          WHERE `ID` LIKE '$option'";
            $result         = $DBOCS->query($getKnownSubnet);

            $i      = 0;
            while ($subNet = $DBOCS->fetch_assoc($result)) {
               $subnet[]      = $subNet;
               $theSubnet[$i] = $subnet[$i]["NETID"];
               $i++;
            }
            $Nets = self::parseArrayToString($theSubnet);
         }
      }
      if ($Nets == "") {
         return array();
      } else {
         $macAdresses  = self::parseArrayToString($knownMacAdresses);
         $percentQuery = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME', CASE WHEN ident.c IS NULL and ipdiscover.c IS NULL THEN 100 WHEN ident.c IS NULL THEN 0 WHEN non_ident.c IS NULL THEN 0 ELSE round(100-(non_ident.c*100/(ident.c+non_ident.c)), 1) END as 'PERCENT' 
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover 
right join 
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices ) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";
         //this is for the right percentage
         $percent      = $DBOCS->query($percentQuery);

         $query = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME'
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover 
right join 
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices WHERE `network_devices`.`MACADDR` NOT IN($macAdresses)) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";

         $result  = $DBOCS->query($query);
         while ($details = $DBOCS->fetch_assoc($result)) {
            $per                = $DBOCS->fetch_assoc($percent);
            $details['PERCENT'] = $per['PERCENT'];
            $subnetsDetails[]   = $details;
         }
         return $subnetsDetails;
      }
   }

   static function ipDiscoverMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;
      $ocsservers          = array();
      echo "<div class='center'>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/ocsinventoryng.png' " .
      "alt='OCS Inventory NG' title='OCS Inventory NG'>";
      echo "</div>";
      $numberActiveServers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', "`is_active`='1'");
      if ($numberActiveServers > 0) {
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php\"
         method='post'>";
         echo "<div class='center'><table class='tab_cadre' width='40%'>";
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
         Dropdown::show('PluginOcsinventoryngOcsServer', array("condition"           => "`id` IN ('" . implode("','", $ocsservers) . "')",
             "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
             "on_change"           => "this.form.submit()",
             "display_emptychoice" => false));

         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         _e('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php\"
                method='post'>";
         echo "<div class='center'><table class='tab_cadre' width='40%'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an subnet', 'ocsinventoryng') .
         "</th></tr>\n";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('subnet') . "</td>";
         echo "<td class='center'>";
         $tab                 = array(Dropdown::EMPTY_VALUE, "All Subnets", "Known Subnets", "Unknown Subnets");
         $subnets             = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
         self::getSubnetsID($subnets["All Subnets"], $tab);
         $_SESSION["subnets"] = $tab;
         Dropdown::showFromArray("subnetsChoise", $tab, array("on_change" => "this.form.submit()", "display_emptychoice" => false));

         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";

         Html::closeForm();
      }
   }

   static function showPercentItem($value, $linkto = "") {

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
   }

   static function showItem($value, $linkto = "", $id = "", $type = "", $checkbox = false, $check = "", $iterator = 0) {
      $out = "<td class='tab_bg_2 rowHover'>";
      if ($checkbox) {
         $out .= "<td><input type='checkbox' name='mactoimport[$iterator][" . $value . "]' " .
                 ($check == "all" ? "checked" : "") . "></td>\n";

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

   static function checkBox($target) {

      echo "<div class='center' width='100%'><a href='" . $target . "?check=all' " .
      "onclick= \"if (markCheckboxes('ipdiscover_form')) return false;\">" . __('Check all') .
      "</a>&nbsp;/&nbsp;\n";
      echo "<a href='" . $target . "?check=none' " .
      "onclick= \"if ( unMarkCheckboxes('ipdiscover_form') ) return false;\">" .
      __('Uncheck all') . "</a></div>\n";
   }

   static function getKnownMacAdresseFromGlpi() {
      global $DB;
      $macAdresses = array();
      $query       = "SELECT `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
                FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`";
      $result      = $DB->query($query);
      while ($res         = $DB->fetch_assoc($result)) {
         $macAdresses[] = $res["macaddress"];
      }
      return $macAdresses;
   }

   static function getHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id, $status, $knownMacAdresses = array()) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query     = "";
      
      if ($status == "inventoried") {
         $query = " SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain` 
         FROM `hardware` 
         LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id` 
         WHERE `networks`.`ipsubnet`='$ipAdress' 
         AND status='Up' 
         ORDER BY `hardware`.`lastdate`";
      } else if ($status == "noninventoried") {
         $query = " SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name` as DNS
              FROM `netmap` 
              LEFT JOIN `networks` 
              ON `netmap`.`mac` =`networks`.`macaddr` 
              WHERE `netmap`.`netid`='$ipAdress' 
              AND (`networks`.`macaddr` IS NULL OR `networks`.`ipsubnet` <> `netmap`.`netid`) 
              AND `netmap`.`mac` NOT IN ( SELECT DISTINCT(`network_devices`.`macaddr`) 
              FROM `network_devices`)
              GROUP BY `netmap`.`mac`
              ORDER BY `netmap`.`ip`";
      } else {
         $macAdresses = self::parseArrayToString($knownMacAdresses);
         $query = "SELECT `network_devices`.`ID`,`network_devices`.`TYPE`,`network_devices`.`DESCRIPTION`,`network_devices`.`USER`,`netmap`.`IP`,`netmap`.`MAC`,`netmap`.`MASK`,`netmap`.`NETID`,`netmap`.`NAME`,`netmap`.`DATE`
              FROM `network_devices`
              LEFT JOIN `netmap` 
              ON `network_devices`.`MACADDR`=`netmap`.`MAC` 
              WHERE `netmap`.`NETID`='$ipAdress'
              AND `network_devices`.`MACADDR` NOT IN($macAdresses)
              GROUP BY `network_devices`.`MACADDR`
              ORDER BY `network_devices`.`TYPE` asc";
      }
      $result   = $DBOCS->query($query);
      $hardware = array();
      while ($res      = $DBOCS->fetch_assoc($result)) {
         $hardware[] = $res;
      }
      return $hardware;
   }
   
   
   static function loadMacConstructor(){
      $macFile=GLPI_ROOT."/plugins/ocsinventoryng/files/macManufacturers.txt";
      $result="";
      if( $file=@fopen($macFile,"r") ) {			
		while (!feof($file)) {				 
			$line  = fgets($file, 4096);
			if( preg_match("/^((?:[a-fA-F0-9]{2}-){2}[a-fA-F0-9]{2})\s+\(.+\)\s+(.+)\s*$/", $line, $result ) ) {
				$_SESSION["OCS"]["IpdiscoverMacConstructors"][mb_strtoupper(str_replace("-",":",$result[1]))] = $result[2];
           
			}				
		}
		fclose($file);			
	}
   }

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
   }

   static function showSubnetsDetails($subnetsArray, $lim = 0) {
      global $CFG_GLPI;
      $start           = 0;
      $output_type     = Search::HTML_OUTPUT; //0
      $return          = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php";
      $link            = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
      $choise          = "subnetsChoise=" . $subnetsArray["subnetsChoise"];
      $subnets         = $subnetsArray["subnets"];
      echo html::printPager($start, count($subnets), $link, $choise);
      echo Search::showNewLine($output_type, true);
      $header_num      = 1;
      echo "<div class='tab_cadre_fixe'><table width='100%'class='tab_cadre_fixe'>\n";
      echo Search::showHeaderItem($output_type, __('Description'), $header_num);
      echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
      echo Search::showHeaderItem($output_type, __('Non Inventoried'), $header_num);
      echo Search::showHeaderItem($output_type, __('Inventoried'), $header_num);
      echo Search::showHeaderItem($output_type, __('Identified'), $header_num);
      echo Search::showHeaderItem($output_type, __('Percent'), $header_num);
      echo Search::showEndLine($output_type);
      $row_num         = 1;
      $modNetwork      = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php";
      $hardwareNetwork = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php";
      $img             = $CFG_GLPI['root_doc'] . "/pics/loader.png";

      //limit number of displayed items
      for ($i = $start; $i < $lim; $i++) {
         $row_num++;
         $name = "unknow";
         echo Search::showNewLine($output_type, $row_num % 2);
         if ($subnets[$i]["NAME"] != "") {
            $name = $subnets[$i]["NAME"];
         }
         
         echo self::showItem($name, $modNetwork, $subnets[$i]["IP"]);
         echo self::showItem($subnets[$i]["IP"]);
         echo self::showItem($subnets[$i]["NON_INVENTORIED"], $hardwareNetwork, $subnets[$i]["IP"], "noninventoried");
         echo self::showItem($subnets[$i]["INVENTORIED"], $hardwareNetwork, $subnets[$i]["IP"], "inventoried");
         echo self::showItem($subnets[$i]["IDENTIFIED"], $hardwareNetwork, $subnets[$i]["IP"], "identified");
         echo "<td>";
         //echo Html::displayProgressBar(10, $subnets[$i]["PERCENT"], array("simple" => true, "forcepadding" => false));
         echo self::showPercentBar($subnets[$i]["PERCENT"]);
         echo "</td>";
      }
      echo "</table></div>\n";
      echo "<div class='center'><a href='$return'>retour</div>";
   }

   static function modifyNetworkForm($ipAdress, $values = array()) {
      global $CFG_GLPI;
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $OCSDB     = $ocsdb->getDB();
      echo "<div class='center'><table class='tab_cadre' width='60%'>";
      echo "<tr class='tab_bg_2'><th colspan='4'>" . __('Modify Subnet', 'ocsinventoryng') . "</th></tr>\n";
      $query     = "SELECT *
              FROM subnet
              WHERE `subnet`.`NETID` = '$ipAdress'";
      $result    = $OCSDB->query($query);
      $target    = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php\"";

      echo "<form name=\"idSelection\" action=\"" . $target . " method='post'>";
      echo "<tr class='tab_bg_2' ><td class='center' >" . __('Subnet Name', 'ocsinventoryng') . "</td>";
      //this is for the identified subnets
      if ($result->num_rows > 0) {
         $res   = $OCSDB->fetch_assoc($result);
         $n     = $res["NAME"];
         $m     = $res["MASK"];
         echo "<td> <input type=\"text\" name=\"subnetName\" value=\"$n\" required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
         echo "<td>";
         $sbnts = array(Dropdown::EMPTY_VALUE);
         self::getAllSubnetsID($sbnts);
         Dropdown::showFromArray('subnetChoise', $sbnts, array('on_change' => 'FillInput();'));
         echo "</td>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";
         echo "<td class=''>" . $ipAdress . "</td><td><input type=\"hidden\" name=\"ip\" value=\"$ipAdress\"></td></tr>";
         echo "<tr class='tab_bg_2' colspan='4'><td class='center'>" . __('Subnet mask') . "</td>";
         echo "<td> <input type=\"text\" name=\"SubnetMask\" value=\"$m\" required></td></tr>";
         echo "<td class='right'><input type='submit' name='Modify' value=\"" . _sx('button', 'Modify') . "\" class='submit'></td>";
         html::closeForm();
         echo "<form name=\"idSelection\" action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?ip=?$ipAdress\" method='post'>";
         echo "<td class='right'> <input type='submit' name='Cancel' value=\"" . _sx('button', 'Cancel') . "\" class='submit'></td></tr></div>";
         html::closeForm();
      }
      //this is for the unidentified subnets
      else {
         echo "<td> <input type=\"text\" name=\"subnetName\" value=\"\" required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
         echo "<td>";
         $sbnts = array(Dropdown::EMPTY_VALUE);
         self::getAllSubnetsID($sbnts);
         Dropdown::showFromArray('subnetChoise', $sbnts, array('on_change' => 'FillInput();'));
         echo "</td>";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";
         echo "<td class=''>" . $ipAdress . "</td><td><input type=\"hidden\" name=\"ip\" value=\"$ipAdress\"></td></tr>";
         echo "<tr class='tab_bg_2' colspan='4'><td class='center'>" . __('Subnet mask') . "</td>";
         echo "<td> <input type=\"text\" name=\"SubnetMask\" value=\"\" required></td></tr>";
         echo "<td class='center'><input type='submit' name='Add' value=\"" . _sx('button', 'Add') . "\" class='submit'></td>";
         html::closeForm();
         echo "<form name=\"idSelection\" action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?ip=?$ipAdress\" method='post'>";
         echo "<td class='right'> <input type='submit' name='Cancel' value=\"" . _sx('button', 'Cancel') . "\" class='submit'></td></tr></div>";
         html::closeForm();
      }
      $addQuery = "";
      //TODOD : do not forget if name or thing was not modified
      if (!empty($values) && $values["subnetChoise"] != "0") {
         $name = $values["subnetName"];
         $id   = $values["subnetChoise"];
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
            $link = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php?subnetsChoise=2"; //2 is for the known subnets
            html::redirect($link);
         }
      }
      echo "</table></div>";
   }

   static function processIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id) {
      global $DB;
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $ocsClient->checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $mac       = $ipDiscoveryObject["macAdress"];
      $query     = "SELECT `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
                FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                WHERE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
                LIKE '$mac'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         $datas = $DB->fetch_array($result);
      }
      return self::importIpDiscover($ipDiscoveryObject,$plugin_ocsinventoryng_ocsservers_id);
   }

   static function importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id) {
      global $DB;
      $res      = null;
      $identify = false;
      if (isset($ipDiscoveryObject["ocsItemType"])&&$ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE) {
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
         case "Device" : $ipDiscoveryObject["glpiItemType"] = "Peripheral";
            break;
         case "Network device": $ipDiscoveryObject["glpiItemType"] = "NetworkEquipment";
            break;
      }

      $input = array(
          'is_dynamic'   => 1,
          'locations_id' => 0,
          'domains_id'   => 0,
          'entities_id'  => $ipDiscoveryObject["entity"],
          'name'         => $ipDiscoveryObject["itemName"],
          'comment'      => $ipDiscoveryObject["itemDescription"]);

      $device = new $ipDiscoveryObject["glpiItemType"]();

      $id = $device->add($input);

      
      if (isset($ipDiscoveryObject["ocsItemType"])) {
         $identify = true;
      }
      if ($id && !$identify) {
         //ipdiscover link
         $date      = date("Y-m-d H:i:s");
         $glpiType  = $ipDiscoveryObject["glpiItemType"];
         $mac       = $ipDiscoveryObject["macAdress"];
         $glpiQuery = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                       (`items_id`,`itemtype`,`macaddress`,`last_update`,`plugin_ocsinventoryng_ocsservers_id`)
                       VALUES('$id','$glpiType','$mac','$date','$plugin_ocsinventoryng_ocsservers_id')";
         $res       = $DB->query($glpiQuery);
      }

      if ($id && $identify) {
         //ipdiscover link
         $ocsClient   = new PluginOcsinventoryngOcsServer();
         $DBOCS       = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
         $date        = date("Y-m-d H:i:s");
         $glpiType    = $ipDiscoveryObject["glpiItemType"];
         $ocsType     = $ipDiscoveryObject["ocsItemType"];
         $mac         = $ipDiscoveryObject["macAdress"];
         $userId      = Session::getLoginUserID();
         $description = $ipDiscoveryObject["itemDescription"];
         $glpiQuery   = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                       (`items_id`,`itemtype`,`macaddress`,`last_update`,`plugin_ocsinventoryng_ocsservers_id`)
                       VALUES('$id','$glpiType','$mac','$date','$plugin_ocsinventoryng_ocsservers_id')";
         $res         = $DB->query($glpiQuery);

         //identify object
         $query       = "SELECT `glpi_users`.`name` 
                         FROM `glpi_users`
                         WHERE glpi_users.id like '$userId'";
         $queryResult = $DB->query($query);
         $userAssoc   = $DB->fetch_assoc($queryResult);
         if ($userAssoc) {
            $user     = $userAssoc["name"];
            $ocsQuery = "INSERT INTO `network_devices` (`description`,`type`,`macaddr`,`user`)
                            VALUES('$description','$ocsType','$mac','$user')";
            $DBOCS->query($ocsQuery);
         }
      }
      if ($res) {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_IMPORTED);
      } else {
         return array('status' => PluginOcsinventoryngOcsServer::IPDISCOVER_FAILED_IMPORT);
      }
   }

   static function updateIpDiscover() {
      
   }

   static function getIpDiscoverobject($macAdresses, $entities = array(), $glpiItemsTypes, $itemsNames = "", $itemsDescription, $ocsItemsTypes = array()) {
      $objectToImport = array();
      $macs           = self::getMacAdressKeyVal($macAdresses);
      if (!empty($entities)) {
         foreach ($macs as $key => $mac) {
            if (!empty($ocsItemsTypes)) {
               $objectToImport[] = array("macAdress" => $mac, "entity" => $entities[$key], "glpiItemType" => $glpiItemsTypes[$key], "ocsItemType" => $ocsItemsTypes[$key], "itemName" => $itemsNames[$key], "itemDescription" => $itemsDescription[$key]);
            } else {
               $objectToImport[] = array("macAdress" => $mac, "entity" => $entities[$key], "glpiItemType" => $glpiItemsTypes[$key], "itemName" => $itemsNames[$key], "itemDescription" => $itemsDescription[$key]);
            }
         }
      } else {
         foreach ($macs as $key => $mac) {
            $ent = null;
            foreach ($_SESSION["glpiactiveentities"] as $e => $eval) {
               $ent = $eval;
            }
            if (!empty($ocsItemsTypes)) {
               $objectToImport[] = array("macAdress" => $mac, "entity" => $ent, "glpiItemType" => $glpiItemsTypes[$key], "ocsItemType" => $ocsItemsTypes[$key], "itemName" => $itemsNames[$key], "itemDescription" => $itemsDescription[$key]);
            } else {
               $objectToImport[] = array("macAdress" => $mac, "entity" => $ent, "glpiItemType" => $glpiItemsTypes[$key], "itemName" => $itemsNames[$key], "itemDescription" => $itemsDescription[$key]);
            }
         }
      }

      return $objectToImport;
   }

   static function showPercentBar($status) {
      if (!is_numeric($status)) {
         return $status;
      }
      if (($status < 0) or ( $status > 100)) {
         return $status;
      }
      return "<div class='percent_bar'><!--" . str_pad($status, 3, "0", STR_PAD_LEFT) . "-->
             <div class='percent_status' style='width:" . $status . "px;'>&nbsp;</div>
             <div class='percent_text'>" . $status . "%</div>
             </div>";
   }

   static function showHardware($hardware, $lim, $start = 0, $ipAdress, $status) {
      global $CFG_GLPI;
      $output_type = Search::HTML_OUTPUT; //0
      $link        = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php";
      $return      = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
      $reload      = "ip=$ipAdress&status=$status";
      $backValues        = "?b[]=$ipAdress&b[]=$status";
      echo html::printPager($start, count($hardware), $link, $reload);
      echo Search::showNewLine($output_type, true);
      if (empty($hardware)) {
         echo "<div class='center b'><br>" . __('No new IPDISCOVER device to import', 'ocsinventoryng') . "</div>";
         Html::footer();
      } else {
         $header_num = 1;
         switch ($status) {
            case "inventoried" :
               echo "<div class='tab_cadre_fixe'>\n";
               echo "<table width='100%' class='tab_cadre_fixe'>";
               echo Search::showHeaderItem($output_type, __('User'), $header_num);
               echo Search::showHeaderItem($output_type, __('Name'), $header_num);
               echo Search::showHeaderItem($output_type, __('System'), $header_num);
               echo Search::showHeaderItem($output_type, __('System Version'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP Adress'), $header_num);
               echo Search::showHeaderItem($output_type, __('Last Inventory'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               for ($i = $start; $i < $lim; $i++) {
                  $row_num++;
                  echo Search::showNewLine($output_type, $row_num % 2);
                  echo self::showItem($hardware[$i]["userid"]);
                  echo self::showItem($hardware[$i]["name"]);
                  echo self::showItem($hardware[$i]["osname"]);
                  echo self::showItem($hardware[$i]["osversion"]);
                  echo self::showItem($hardware[$i]["ipaddr"]);
                  echo self::showItem(html::convDateTime($hardware[$i]["lastdate"]));
                  echo Search::showEndLine($output_type);
               }
               echo "</table></div>\n";
               echo "<div class='center'><a href='$return'>retour</div>";
               break;

            case "noninventoried" :
               $entities = array("id" => array(Dropdown::EMPTY_VALUE), "name" => array(Dropdown::EMPTY_VALUE), "entities_id" => array(Dropdown::EMPTY_VALUE));
               $ocsTypes = array("id" => array(Dropdown::EMPTY_VALUE), "name" => array(Dropdown::EMPTY_VALUE));
               $link     = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
               $target   = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php".$backValues;

               self::checkBox($target);
               echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
               echo "<table width='100%' class='tab_cadre_fixe'>";
               echo "<div class='center' style=\"width=100%\">";
               echo "<input type='submit' class='submit' name='IdentifyAndImport'  value=\"" . _sx('button', 'Import') . "\">";
               self::getOCSTypes($ocsTypes);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Mask'), $header_num);
               echo Search::showHeaderItem($output_type, __('Date'), $header_num);
               echo Search::showHeaderItem($output_type, __('DNS'), $header_num);
               echo Search::showHeaderItem($output_type, __('OCS Type'), $header_num);
               echo Search::showHeaderItem($output_type, __('Description'), $header_num);
               echo Search::showHeaderItem($output_type, __('Name'), $header_num);
               if (Session::isMultiEntitiesMode()) {
                  echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
               }
               echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               $ocstypr=array();
               foreach ($ocsTypes["name"] as $items){
                  $ocstypr[$items]=$items;
               }
               for ($i = $start; $i < $lim; $i++) {
                  $row_num++;
                  echo Search::showNewLine($output_type, $row_num % 2);
                  echo self::showItem($hardware[$i]["ip"]);
                  $macConstructor = "";
                  if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                     if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"][mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                        $macConstructor = $_SESSION["OCS"]["IpdiscoverMacConstructors"][mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                     } else {
                        $macConstructor = __("unknow");
                     }
                  }
                  $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                  echo self::showItem($mac);
                  echo self::showItem($hardware[$i]["mask"]);
                  echo self::showItem(Html::convDateTime($hardware[$i]["date"]));
                  echo self::showItem($hardware[$i]["DNS"]);
                  echo "<td>";
                  Dropdown::showFromArray("ocsitemstype[$i]", $ocstypr);
                  echo "</td>";
                  echo "<td><input type=\"text\" name='itemsdescription[" . $i . "]' value=\"\" ></td>";
                  echo "<td><input type=\"text\" name='itemsname[" . $i . "]' value=\"\"></td>";
                  if (Session::isMultiEntitiesMode()) {
                     echo "<td>";
                     Entity::dropdown(array('name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]));
                     echo "</td>";
                  }
                  $itemstypes = array(Dropdown::EMPTY_VALUE);
                  foreach (self::$hardwareItemTypes as $items) {
                     $itemstypes[$items] = __($items);
                  }
                  echo "<td>";
                  Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                  echo "</td>";
                  echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
               }
               echo "</table></div>\n";
               echo "<div class='center' style=\"width=100%\">\n<input type='submit' class='submit' name='IdentifyAndImport'  value=\"" . _sx('button', 'Import') . "\"></div>";
               html::closeForm();
               self::checkBox($target);
               break;

            default :

               $entities = array("id" => array(Dropdown::EMPTY_VALUE), "name" => array(Dropdown::EMPTY_VALUE), "entities_id" => array(Dropdown::EMPTY_VALUE));
               $link     = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
               $target   = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.import.php" . $backValues;
               self::checkBox($target);
               echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
               echo "<table width='100%' class='tab_cadre_fixe'>";
               echo "<div class='center' style=\"width=100%\">";
               echo "<input type='submit' class='submit' name='Import'  value=\"" . _sx('button', 'Import') . "\">";
               echo Search::showHeaderItem($output_type, __('OCS Type'), $header_num);
               echo Search::showHeaderItem($output_type, __('Description'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Mac address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Date'), $header_num);
               if (Session::isMultiEntitiesMode()) {
                  echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
               }
               echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('Name'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
               echo Search::showEndLine($output_type);
               $row_num = 1;
               for ($i = $start; $i < $lim; $i++) {
                  $row_num++;
                  echo Search::showNewLine($output_type, $row_num % 2);
                  echo self::showItem($description = $hardware[$i]["TYPE"]);
                  echo self::showItem($hardware[$i]["DESCRIPTION"]);
                  echo self::showItem($hardware[$i]["IP"]);
                  $macConstructor = "";
                  if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                     if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"][mb_strtoupper(substr($hardware[$i]["MAC"], 0, 8))])) {
                        $macConstructor = $_SESSION["OCS"]["IpdiscoverMacConstructors"][mb_strtoupper(substr($hardware[$i]["MAC"], 0, 8))];
                     } else {
                        $macConstructor = __("unknow");
                     }
                  }
                  $mac = $hardware[$i]["MAC"] . "<small> ( " . $macConstructor . " )</small>";
                  echo self::showItem($mac);
                  echo self::showItem(Html::convDateTime($hardware[$i]["DATE"]));
                  if (Session::isMultiEntitiesMode()) {
                     echo "<td>";
                     Entity::dropdown(array('name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]));
                     echo "</td>";
                  }
                  $itemstypes = array(Dropdown::EMPTY_VALUE);
                  foreach (self::$hardwareItemTypes as $items) {
                     $itemstypes[$items] = __($items);
                  }
                  echo "<td>";
                  Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                  echo "</td>";
                  echo "<td><input type=\"text\" name='itemsname[" . $i . "]' value=\"\"></td>";
                  echo self::showItem($hardware[$i]["MAC"], "", "", "", true, "", $i);
                  echo "<td><input type=\"hidden\" name='itemsdescription[" . $i . "]' value=\"$description\" ></td>";
               }
               echo "</table></div>";
               echo "<div class='center' style=\"width=100%\"><input type='submit' class='submit' name='Import'  value=\"" . _sx('button', 'Import') . "\"></div>";
               html::closeForm();
               self::checkBox($target);

               break;
         }
      }
   }
   
   static function getMacAdressKeyVal($macAdresses) {
      $keys = array();
      foreach ($macAdresses as $key => $val) {
         foreach ($val as $mac => $on) {
            $keys[$key] = $mac;
         }
      }
      return $keys;
   }

}

?>