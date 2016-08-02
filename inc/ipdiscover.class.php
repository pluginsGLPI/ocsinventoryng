<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * IpDiscover class
 */
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//class PluginOcsinventoryngIpDiscover extends CommonDBTM{
   class PluginOcsinventoryngIpDiscover extends CommonGLPI {

    
      static protected $notable                = false;
     //public $taborientation          = 'vertical';
       //var $fields                              = array();//need it for Search::show('PluginOcsinventoryngIpDiscover');
   
   static function getTypeName($nb = 0) {
      return _n('OCS Inventory NG ', 'OCS Inventorys NG', $nb, 'ocsinventoryng');
   }
   

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case __CLASS__ :

            $ong[0] = __('Server OCS Inventory NG', 'ocsinventoryng');
            $ong[1] = __('SNMP', 'ocsinventoryng');
            $ong[2] = __('IPDISCOVER', 'ocsinventoryng');
            return $ong;
         default :
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 0,$withtemplate = 0) {

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
   
   
   //those functions are necessary when you want to use Search::show('PluginOcsinventoryngIpDiscover');
   /*function getSearchOptions() {

      $tab = array();

      $tab['common'] = self::getTypeName();

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;

      $tab[3]['table'] = $this->getTable();
      $tab[3]['field'] = 'ocs_db_host';
      $tab[3]['name']  = __('Server');

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'is_active';
      $tab[6]['name']     = __('Active');
      $tab[6]['datatype'] = 'bool';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = __('Last update');
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = __('Comments');
      $tab[16]['datatype'] = 'text';

      $tab[17]['table']    = $this->getTable();
      $tab[17]['field']    = 'use_massimport';
      $tab[17]['name']     = __('Expert sync mode', 'ocsinventoryng');
      $tab[17]['datatype'] = 'bool';

      $tab[18]['table']    = $this->getTable();
      $tab[18]['field']    = 'ocs_db_utf8';
      $tab[18]['name']     = __('Database in UTF8', 'ocsinventoryng');
      $tab[18]['datatype'] = 'bool';

      return $tab;
   }
   
   
   static function getTable() {
      if (static::$notable) {
         return '';
      }

      if (empty($_SESSION['glpi_table_of'][get_called_class()])) {
         $_SESSION['glpi_table_of'][get_called_class()] = getTableForItemType(get_called_class());
      }

      return $_SESSION['glpi_table_of'][get_called_class()];
   }
   
   
   function maybeDeleted() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return array_key_exists('is_deleted', $this->fields);
   }
   
   
   
   function getEmpty() {
      global $DB;

      //make an empty database object
      $table = $this->getTable();

      if (!empty($table) &&
          ($fields = $DB->list_fields($table))) {

         foreach ($fields as $key => $val) {
            $this->fields[$key] = "";
         }
      } else {
         return false;
      }

      if (array_key_exists('entities_id',$this->fields)
          && isset($_SESSION["glpiactive_entity"])) {
         $this->fields['entities_id'] = $_SESSION["glpiactive_entity"];
      }

      $this->post_getEmpty();

      // Call the plugin hook - $this->fields can be altered
      Plugin::doHook("item_empty", $this);
      return true;
   }
   
   
   function isEntityAssign() {

      if (!array_key_exists('id', $this->fields)) {
         $this->getEmpty();
      }
      return array_key_exists('entities_id', $this->fields);
   }
   
   function maybeRecursive() {

      if (!array_key_exists('id',$this->fields)) {
         $this->getEmpty();
      }
      return array_key_exists('is_recursive', $this->fields);
   }
   
   function maybeTemplate() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return isset($this->fields['is_template']);
   }*/
   

   public static function parseSubnetsToString($ipArray=array()) {
      $ipString = "";
      for ($i = 0; $i < sizeof($ipArray); $i++) {
         if ($i == sizeof($ipArray)-1) {
            $ipString .="'" . $ipArray[$i] . "'";
         } else {
            $ipString .="'" . $ipArray[$i] . "'" . ",";
         }
      }
      return $ipString;
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
         $outputs[] = $subNetId["TVALUE"];
      }
   }
   
   
   public static function getSubnetsID($inputs,&$outputs) {
      foreach ($inputs as $subnets) {
         if (isset($subnets["ID"])) {
            if ($subnets["ID"] != null&&!in_array($subnets["ID"], $outputs)) {
               $outputs[] = $subnets["ID"];
            }
         }
      }
   }
   
   

   public static function countSubnetsID(&$count) {
      $ocsClient = new PluginOcsinventoryngOcsServer();
      $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
      $query="SELECT MAX(`config`.`IVALUE`) as MAX
              FROM `config` 
              WHERE 
              `config`.`NAME` 
              LIKE 'ID_IPDISCOVER_%'";
      $result    = $DBOCS->query($query);
      $subNetId  = $DBOCS->fetch_assoc($result);
         $count = intval($subNetId["MAX"]);
      
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

   public static function showSubnets($plugin_ocsinventoryng_ocsservers_id, $subnets, $option = "") {
      //this query displays all the elements on the the networks we found :
      $subnetsDetails = array();
      $knownNets      = "";
      $unknownNets    = "";
      $Nets           = "";
      $ocsClient      = new PluginOcsinventoryngOcsServer();
      $DBOCS          = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      if ($option == "Known Subnets") {
         $knownNets = self::parseSubnetsToString($subnets["knownIP"]);
         $Nets      = $knownNets;
      } else if ($option == "Unknown Subnets") {
         $unknownNets = self::parseSubnetsToString($subnets["unknownIP"]);
         $Nets        = $unknownNets;
      } else if ($option == "All Subnets") {
         $knownNets   = self::parseSubnetsToString($subnets["knownIP"]);
         $unknownNets = self::parseSubnetsToString($subnets["unknownIP"]);
         $Nets        = $knownNets . "," . $unknownNets;
      } else {
         if ($option != "") {
            $theSubnet      = array();
            $getKnownSubnet = "SELECT `subnet`.`NETID`
                          FROM `subnet`
                          WHERE `ID` LIKE '$option'";
            $result         = $DBOCS->query($getKnownSubnet);
            
            $i=0;
            while ($subNet = $DBOCS->fetch_assoc($result)) {
            $subnet[] = $subNet;
            $theSubnet[$i]=$subnet[$i]["NETID"];
            $i++;
            }
            $Nets = self::parseSubnetsToString($theSubnet);
         }
      }
      if ($Nets == "") {
         return array();
      } else {
         $query = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME', CASE WHEN ident.c IS NULL and ipdiscover.c IS NULL THEN 100 WHEN ident.c IS NULL THEN 0 ELSE round(100-(non_ident.c*100/(ident.c+non_ident.c)), 1) END as 'PERCENT' 
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover 
right join 
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";
         
         $result = $DBOCS->query($query);
         while ($details = $DBOCS->fetch_assoc($result)) {
            $subnetsDetails[] = $details;
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
         $tab=array("-----","All Subnets","Known Subnets","Unknown Subnets");
         $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
         self::getSubnetsID($subnets["All Subnets"],$tab);
         $_SESSION["subnets"]=$tab;
         Dropdown::showFromArray("subnetsChoise",$tab,array("on_change"           => "this.form.submit()","display_emptychoice" => false));
         
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
        
         Html::closeForm();
 
      }
   }
   
   
   
static function showPercentItem($value,$linkto = "") {

      $width="200px";
      $out = "<th class=''>";
      if (!empty($linkto)) {
         $out .="<img src=\"$linkto\" alt = \"percentage\" width=\"$value px\" height=\"10px\" ";
       
      }
      if (!empty($linkto)) {
         $out .= ";>";
      }
      $out .= $value;
      $out .= "</th>\n";
      return $out;
   }
   

      
   
   static function showItem($value, $linkto="",$ip="") {
      $out = "<th class=''>";
      
      if (!empty($linkto)) {
         $link = $linkto;
         $id=$ip;
         $out .= "<a href=\"$link"."?ip=$id\">";  
      }

      $out .= $value;
      if (!empty($linkto)) {
         $out .= "</a>";
      }
      $out .= "</th>\n";
      return $out;
   }
   
   
   static function fillInputFromDropDown($name,$list=array(),$inputToFill){
      //$form="<form id=\"$name\" name=\"$name\" method='post'>";
      $form ="<select id=\"$inputToFill\" onchange=\"fillInputTex('$inputToFill')\">";
      foreach ($list as $choise){
         $form.= "<option value=\"$choise\">$choise</option>";
      }
      $form .="</select>";//</form>";
      return $form;
   }
   
   
static function fillInputFromDropDown1($name, $list = array(), $inputToFill) {
      //$form="<form id=\"$name\" name=\"$name\" method='post'>";
      $form = "<select name=\"$inputToFill\" id=\"$inputToFill\" onchange=\"fillInput('$inputToFill')\">";
      foreach ($list as $choise) {
         $form.= "<option value=\"$choise\">$choise</option>";
      }
      $form .="</select>"; //</form>";
      /*$form.="<script type=\"text/javascript\">";
       $form.="function FillInput()
        {
        var choise=document.getElementById(\"$inputToFill\");
        var option=choise.options[choise.selectedIndex].text;
        document.getElementById(\"$inputToFill\").value=option;
       }";
       $form.="</scrip>";*/
      return $form;
   }
   
   static function getInventoriedComputers($ipAdress,$plugin_ocsinventoryng_ocsservers_id){
      $ocsClient      = new PluginOcsinventoryngOcsServer();
      $DBOCS          = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query=" SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain` 
         FROM `hardware` 
         LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id` 
         WHERE `networks`.`ipsubnet`='$ipAdress' 
         AND status='Up' 
         ORDER BY `hardware`.`lastdate`";
      $result=$DBOCS->query($query);
      $inv=array();
      while($res=$DBOCS->fetch_assoc($result)){
         $inv[]=$res;
      }
      return $inv;
   }
   
   
   static function getNonInventoriedHardware($ipAdress,$plugin_ocsinventoryng_ocsservers_id){
      $ocsClient      = new PluginOcsinventoryngOcsServer();
      $DBOCS          = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
      $query=" SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name`
         FROM `netmap` 
         LEFT JOIN `networks` ON `netmap`.`mac `=`networks`.`macaddr`
         WHERE `netmap`.`netid`='172.14.0.0'
         AND (`networks`.`macaddr` IS NULL 
         OR `networks`.`ipsubnet` <> `netmap`.`netid`) 
         AND `netmap`.`mac` 
         NOT IN (
         SELECT DISTINCT(`network_devices`.`macaddr`) 
         FROM `network_devices`) 
         ORDER BY `netmap`.`ip`";
      $result=$DBOCS->query($query);
      $nonInv=array();
      while($res=$DBOCS->fetch_assoc($result)){
         $nonInv[]=$res;
      }
      return $nonInv;
   }
   

   static function showSubnetsDetails($subnetsArray, $lim = 0) {
      global $CFG_GLPI;
      $start         = 0;
      $output_type   = Search::HTML_OUTPUT; //0
      $begin_display = $start;
      $end_display   = $start + $lim;
      $nbcols        = 1;
      $link          = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
      $choise        = "subnetsChoise=" . $subnetsArray["subnetsChoise"];
      $subnets       = $subnetsArray["subnets"];
      echo html::printPager($start, count($subnets), $link, $choise);
      $networks      = $_SESSION["subnets"][$subnetsArray["subnetsChoise"]];
      echo Search::showNewLine($output_type, true);
      echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
      $header_num    = 1;

      echo Search::showHeaderItem($output_type, __('Description'), $header_num);
      echo Search::showHeaderItem($output_type, __('IP Adress'), $header_num);
      echo Search::showHeaderItem($output_type, __('Non Inventoried'), $header_num);
      echo Search::showHeaderItem($output_type, __('Inventoried'), $header_num);
      echo Search::showHeaderItem($output_type, __('Identified'), $header_num);
      echo Search::showHeaderItem($output_type, __('Percent'), $header_num);
      echo Search::showEndLine($output_type);
      $row_num    = 1;
      $modNetwork = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoveridentifynetwork.form.php";
      $invNetwork      = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoverinventoriedcomputers.form.php";
      $img        = $CFG_GLPI['root_doc'] . "/pics/loader.png";

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
         echo self::showItem($subnets[$i]["NON_INVENTORIED"], $modNetwork, $subnets[$i]["IP"]);
         echo self::showItem($subnets[$i]["INVENTORIED"], $invNetwork, $subnets[$i]["IP"]);
         echo self::showItem($subnets[$i]["IDENTIFIED"], $modNetwork, $subnets[$i]["IP"]);
         echo self::showPercentItem($subnets[$i]["PERCENT"], $img);
      }
      //Html::createProgressBar(__('Work in progress...'));
      //html::progressBar('doaction_progress', array());
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
      echo "<form name=\"idSelection\" action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoveridentifynetwork.form.php\" method='post'>";
      echo "<tr class='tab_bg_2' ><td class='center' >" . __('Subnet Name') . "</td>";
      //this is for the identified subnets
      if ($result->num_rows > 0) {
         $res     = $OCSDB->fetch_assoc($result);
         $n       = $res["NAME"];
         echo "<td> <input type=\"text\" name=\"subnetName\" value=\"$n\" required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID') . "</td>";
         echo "<td>";
         $sbnts   = array("-----");
         $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
         self::getAllSubnetsID($sbnts);
         echo "<select id=\"fillerChoise\">";
         foreach ($sbnts as $choise) {
            echo "<option value=\"$choise\">$choise</option>";
         }
         echo "</td>";
         echo "<td>" . __('Or add new ID') . "</td>";
         echo "<td> <input type=\"text\" id=\"inputFiller\"  required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('IP Adress') . "</td>";
         echo "<td class=''>" . $ipAdress . "</td><td><input type=\"hidden\" name=\"ip\" value=\"$ipAdress\"></td></tr>";
         echo "<tr class='tab_bg_2' colspan='4'><td class='center'>" . __('Mask') . "</td>";
         echo "<td> <input type=\"text\" name=\"SubnetMask\" value=\"\" required></td></tr>";
         echo "<td class='center'><input type='submit' name='add' value=\""._sx('button', 'Modify')."\" class='submit'></td>";
         echo "<td class='right'> <input type='submit' name='Cancel' value=\""._sx('button', 'Cancel')."\" class='submit'></td></tr></div>";
         html::closeForm();
      }
      //this is for the unidentified subnets
      else {
         echo "<td> <input type=\"text\" name=\"subnetName\" value=\"\" required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID') . "</td>";
         echo "<td>";
         $sbnts   = array("-----");
         $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
         self::getAllSubnetsID($sbnts);
         echo "<select id=\"fillerChoise\" onchange==\"fillInput()\">";
         foreach ($sbnts as $choise) {
            echo "<option value=\"$choise\">$choise</option>";
         }
         echo "</select>";
         echo "</td>";
         echo "<td>" . __('Or add new ID') . "</td>";
         echo "<td> <input type=\"text\" id=\"inputFiller\"  required></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . __('IP Adress') . "</td>";
         echo "<td class=''>" . $ipAdress . "</td><td><input type=\"hidden\" name=\"ip\" value=\"$ipAdress\"></td></tr>";
         echo "<tr class='tab_bg_2' colspan='4'><td class='center'>" . __('Mask') . "</td>";
         echo "<td> <input type=\"text\" name=\"SubnetMask\" value=\"\" required></td></tr>";
         echo "<td class='center'><input type='submit' name='add' value=\""._sx('button', 'Modify')."\" class='submit'></td>";
         echo "<td class='right'> <input type='submit' name='Cancel' value=\""._sx('button', 'Cancel')."\" class='submit'></td></tr></div>";
         html::closeForm();
      }
   }
   
   
   static function inventoridComputers($inventoriedComputers,$lim,$ipAdress){
      global $CFG_GLPI;
      $start         = 0;
      $output_type   = Search::HTML_OUTPUT; //0
      $begin_display = $start;
      $end_display   = $start + $lim;
      $nbcols        = 1;
      $link          = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoverinventoriedcomputers.form.php";
      $reload        = "ip=$ipAdress" ;
      echo html::printPager($start, count($inventoriedComputers), $link,$reload);
      echo Search::showNewLine($output_type, true);
      echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
      $header_num    = 1;

      echo Search::showHeaderItem($output_type, __('User'), $header_num);
      echo Search::showHeaderItem($output_type, __('Name'), $header_num);
      echo Search::showHeaderItem($output_type, __('System'), $header_num);
      echo Search::showHeaderItem($output_type, __('System Version'), $header_num);
      echo Search::showHeaderItem($output_type, __('IP Adress'), $header_num);
      echo Search::showHeaderItem($output_type, __('Last Inventory'), $header_num);
      echo Search::showEndLine($output_type);
      $row_num=1;
      for ($i = $start; $i < $lim; $i++) {
         $row_num++;
         echo Search::showNewLine($output_type, $row_num % 2);
      echo self::showItem($inventoriedComputers[$i]["userid"]);
      echo self::showItem($inventoriedComputers[$i]["name"]);
      echo self::showItem($inventoriedComputers[$i]["osname"]);
      echo self::showItem($inventoriedComputers[$i]["osversion"]);
      echo self::showItem($inventoriedComputers[$i]["ipaddr"]);
      echo self::showItem($inventoriedComputers[$i]["lastdate"]);
      echo Search::showEndLine($output_type);
      }
   }

   static function ipDiscFooter() {
      global $CFG_GLPI, $TIMER_DEBUG;
      echo "<table width='100%' height='18px' class='ipDisc'><tr><td class='left'><span class='copyright'>";
      $timedebug = sprintf(_n('%s second', '%s seconds', $TIMER_DEBUG->getTime()), $TIMER_DEBUG->getTime());

      if (function_exists("memory_get_usage")) {
         $timedebug = sprintf(__('%1$s - %2$s'), $timedebug, Toolbox::getSize(memory_get_usage()));
      }
      echo $timedebug;
      echo "</span></td>";
      echo "<td class='right'>";
      echo "<a href='http://glpi-project.org/'>";
      echo "<span class='copyright'>GLPI " . $CFG_GLPI["version"] . " Copyright (C)" .
      " 2015" .
      /* "-".date("Y"). */ // TODO, decomment this in 2016
      " by Teclib'" .
      " - Copyright (C) 2003-2015 INDEPNET Development Team" .
      "</span>";
      echo "</a></td>";
      echo "</tr></table>";
   }

}

?>