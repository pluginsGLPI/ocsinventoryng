<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
  -------------------------------------------------------------------------
  ocsinventoryng plugin for GLPI
  Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

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
namespace GlpiPlugin\Ocsinventoryng;

use Ajax;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Entity;
use Html;
use IPAddress;
use NetworkPort;
use Search;
use Session;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class IpdiscoverOcslink
 */
class IpdiscoverOcslink extends CommonDBTM
{
    public static $hardwareItemTypes = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

    /** Lists of the subnet pages (front/ipdiscover.import.php?status=) */
    public const STATUSES = ['inventoried', 'imported', 'noninventoried', 'identified'];

    /** Actions of the subnet pages (front/ipdiscover.php?action=) */
    public const ACTIONS = ['import', 'link'];

    /**
     * A subnet address posted to the pages: an IP address only
     *
     * @param mixed $ip
     *
     * @return string
     */
    public static function checkIp($ip): string
    {
        $ip = (string) $ip;
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }

        return $ip;
    }

    /**
     * @param mixed $mac
     *
     * @return bool whether it is a MAC address ("aa:bb:cc:dd:ee:ff")
     */
    public static function isMac($mac): bool
    {
        return is_string($mac) && preg_match('/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/', $mac) === 1;
    }

    /**
     * @param mixed $status
     *
     * @return string a known status, "identified" by default as before
     */
    public static function checkStatus($status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'identified';
    }

    /**
     * @param mixed $action
     *
     * @return string a known action, "import" by default
     */
    public static function checkAction($action): string
    {
        return in_array($action, self::ACTIONS, true) ? $action : 'import';
    }

    /**
     * The type of the GLPI item imported or linked: one of the hardware types only
     *
     * @param mixed $itemtype
     *
     * @return CommonDBTM|null
     */
    public static function getHardwareItem($itemtype): ?CommonDBTM
    {
        if (!in_array($itemtype, self::$hardwareItemTypes, true)) {
            return null;
        }
        $item = getItemForItemtype($itemtype);

        return $item instanceof CommonDBTM ? $item : null;
    }

    /**
     * @param int $nb
     *
     * @return string|translated
     */
    public static function getTypeName($nb = 0)
    {
        return __('IPDiscover Import', 'ocsinventoryng');
    }


    /**
     * parse array with ip or mac into one string
     *
     * @param array|type $array
     *
     * @return string $
     */
    public static function parseArrayToString($array = [])
    {
        // The values end in "IN (...)" lists of the OCS queries: each one quoted
        if (count($array) == 0) {
            return "''";
        }

        return implode(',', array_map(
            static fn($value) => \DBmysql::quoteValue((string) $value),
            array_values($array)
        ));
    }

    /**
     * get subnets name
     *
     * @param  $inputs array
     * @param  $outputs array
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
     *
     * @param   array
     */
    public static function getAllSubnetsID(&$outputs)
    {
        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
        $query     = "SELECT `config`.`TVALUE`
                    FROM `config`
                    WHERE `config`.`NAME`
                    LIKE 'ID_IPDISCOVER_%'";
        $result    = $DBOCS->doQuery($query);
        while ($subNetId = $DBOCS->fetchAssoc($result)) {
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
     *
     * @return int|mixed
     */
    public static function getSubnetIDbyIP($ipAdress)
    {

        $subnet    = -1;
        $ocsClient = new OcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();

        $res = $OCSDB->request([
            'FROM'  => 'subnet',
            'WHERE' => ['NETID' => (string) $ipAdress],
            'LIMIT' => 1,
        ])->current();
        if ($res) {
            $subnet = array_search($res["ID"], $_SESSION["subnets"] ?? []);
        }
        return $subnet;
    }

    /**
     * @param $ipAdress
     *
     * @return string
     */
    public static function getSubnetNamebyIP($ipAdress)
    {

        $name      = "";
        $ocsClient = new OcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();

        $res = $OCSDB->request([
            'FROM'  => 'subnet',
            'WHERE' => ['NETID' => (string) $ipAdress],
            'LIMIT' => 1,
        ])->current();
        if ($res) {
            $name = $res["NAME"];
        }
        return $name;
    }

    /**
     * @param $count
     */
    public static function countSubnetsID(&$count)
    {
        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
        $query     = "SELECT MAX(`config`.`IVALUE`) as MAX
                 FROM `config`
                 WHERE `config`.`NAME`
                 LIKE 'ID_IPDISCOVER_%'";
        $result    = $DBOCS->doQuery($query);
        $subNetId  = $DBOCS->fetchAssoc($result);
        $count     = intval($subNetId["MAX"]);
    }


    /**
     * get the OCS types from DB
     *
     * @param type $out array
     */
    public static function getOCSTypes(&$out)
    {
        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
        $query     = "SELECT `devicetype`.`id` , `devicetype`.`name`
                    FROM `devicetype`";
        $result    = $DBOCS->doQuery($query);

        while ($ent = $DBOCS->fetchAssoc($result)) {
            $out["id"][]   = $ent["id"];
            $out["name"][] = $ent["name"];
        }
    }


    /**
     * get all the subnets informations
     *
     * @param type $plugin_ocsinventoryng_ocsservers_id
     *
     * @return array with All Subnets , Known Subnets, Unknown Subnets, knownIP, unknownIP
     */
    public static function getSubnets($plugin_ocsinventoryng_ocsservers_id)
    {
        $subnets   = [];
        $unknown   = [];
        $known     = [];
        $unknownIP = [];
        $knownIP   = [];
        //$IP        = array();
        $query     = "SELECT DISTINCT `networks`.`IPSUBNET`,`subnet`.`NAME`,`subnet`.`ID`
                     FROM `networks`
                     LEFT JOIN `subnet`
                     ON (`networks`.`IPSUBNET` = `subnet`.`NETID`) ,`accountinfo`
                     WHERE `networks`.`HARDWARE_ID`=`accountinfo`.`HARDWARE_ID`
                     AND `networks`.`STATUS`='Up'";
        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
        $result    = $DBOCS->doQuery($query);
        while ($subNet = $DBOCS->fetchAssoc($result)) {
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
        return ["All Subnets"     => $subnets,
            "Known Subnets"   => $known,
            "Unknown Subnets" => $unknown,
            "knownIP"         => $knownIP,
            "unknownIP"       => $unknownIP];
    }


    /**
     * @param        $plugin_ocsinventoryng_ocsservers_id
     * @param        $subnets
     * @param        $knownMacAdresses
     * @param string $option
     *
     * @return array
     */
    public static function showSubnets($plugin_ocsinventoryng_ocsservers_id, $subnets, $knownMacAdresses, $option = "")
    {
        //this query displays all the elements on the the networks we found :
        $subnetsDetails = [];
        $knownNets      = "";
        $unknownNets    = "";
        $Nets           = "";
        $ocsClient      = new OcsServer();
        $DBOCS          = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
        if ($option == "1") {
            $knownNets   = self::parseArrayToString($subnets["knownIP"]);
            $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
            $Nets        = $knownNets . "," . $unknownNets;
        } elseif ($option == "2") {
            $knownNets = self::parseArrayToString($subnets["knownIP"]);
            $Nets      = $knownNets;
        } elseif ($option == "3") {
            $unknownNets = self::parseArrayToString($subnets["unknownIP"]);
            $Nets        = $unknownNets;
        } else {
            if ($option != "") {
                // The subnet chosen in the list of the page: the value is bound
                $theSubnet = [];
                foreach ($DBOCS->request([
                    'SELECT' => 'NETID',
                    'FROM'   => 'subnet',
                    'WHERE'  => ['ID' => (string) $option],
                ]) as $subNet) {
                    $theSubnet[] = $subNet["NETID"];
                }
                $Nets = self::parseArrayToString($theSubnet);
            }
        }
        if ($Nets == "") {
            return [];
        } else {
            $macAdresses  = self::parseArrayToString($knownMacAdresses);
            $percentQuery = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME', CASE WHEN ident.c IS NULL and ipdiscover.c IS NULL THEN 100 WHEN non_ident.c IS NULL and ipdiscover.c IS NOT NULL THEN 100 WHEN ident.c IS NULL THEN round(inv.c * 100 / (non_ident.c + inv.c),1) ELSE round((inv.c + ident.c) * 100 / (non_ident.c + inv.c),1) END as 'PERCENT'
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover
right join
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices ) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";
            //this is for the right percentage
            $percent = $DBOCS->doQuery($percentQuery);

            $query = " SELECT * from (select inv.RSX as IP, inv.c as 'INVENTORIED', non_ident.c as 'NON_INVENTORIED', ipdiscover.c as 'IPDISCOVER', ident.c as 'IDENTIFIED', inv.name as 'NAME'
from (SELECT COUNT(DISTINCT hardware_id) as c, 'IPDISCOVER' as TYPE, tvalue as RSX FROM devices WHERE name = 'IPDISCOVER' and tvalue in (" . $Nets . ")
GROUP BY tvalue) ipdiscover
right join
(SELECT count(distinct(hardware_id)) as c, 'INVENTORIED' as TYPE, ipsubnet as RSX, subnet.name as name FROM networks left join subnet on networks.ipsubnet = subnet.netid WHERE ipsubnet in (" . $Nets . ")
and status = 'Up' GROUP BY ipsubnet) inv on ipdiscover.RSX = inv.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'IDENTIFIED' as TYPE, netid as RSX FROM netmap WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices WHERE `network_devices`.`MACADDR` NOT IN($macAdresses)) and netid in (" . $Nets . ")
GROUP BY netid) ident on ipdiscover.RSX = ident.RSX left join (SELECT COUNT(DISTINCT mac) as c, 'NON IDENTIFIED' as TYPE, netid as RSX FROM netmap n LEFT JOIN networks ns ON ns.macaddr = n.mac WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) and (ns.macaddr IS NULL OR ns.IPSUBNET <> n.netid) and n.netid in (" . $Nets . ")
GROUP BY netid) non_ident on non_ident.RSX = inv.RSX )nonidentified order by IP asc";

            $result = $DBOCS->doQuery($query);
            while ($details = $DBOCS->fetchAssoc($result)) {
                $per                = $DBOCS->fetchAssoc($percent);
                $details['PERCENT'] = $per['PERCENT'];
                $subnetsDetails[]   = $details;
            }
            return $subnetsDetails;
        }
    }

    public static function ipDiscoverMenu()
    {
        $dbu = new DbUtils();
        if ($dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]) > 0) {
            OcsServer::showServerChoice();

            echo "<div class='container-lg my-3'><div class='row row-cards g-3'>";
            echo "<div class='col-12 col-lg-6'>";
            self::showSubnetSearchForm("import");
            echo "</div><div class='col-12 col-lg-6'>";
            self::showSubnetSearchForm("link");
            echo "</div>";
            echo "</div></div>";
        }
    }

    /**
     * @param $action
     */
    public static function showSubnetSearchForm($action)
    {
        global $CFG_GLPI;

        $action = self::checkAction($action);
        echo "<form action=\"" . PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?action=$action\"
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
        echo "<tr class='tab_bg_2'><td class='center'>" . __('Subnet') . "</td>";
        echo "<td class='center'>";
        $tab = ['0' => Dropdown::EMPTY_VALUE,
            '1' => __('All Subnets', 'ocsinventoryng'),
            '2' => __('Known Subnets', 'ocsinventoryng'),
            '3' => __('Unknown Subnets', 'ocsinventoryng')];

        $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        self::getSubnetsID($subnets["All Subnets"], $tab);
        $_SESSION["subnets"] = $tab;
        Dropdown::showFromArray("subnetsChoice", $tab, ["on_change" => "this.form.submit()", "display_emptychoice" => false]);
        echo "</td></tr>";
        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * @param        $value
     * @param string $linkto
     * @param string $id
     * @param string $type
     * @param bool   $checkbox
     * @param string $check
     * @param int    $iterator
     *
     * @return string
     */
    public static function showItem($value, $linkto = "", $id = "", $type = "", $checkbox = false, $check = "", $iterator = 0)
    {
        $out = "<td>";
        if ($checkbox) {
            // The value is a MAC address or an id read from the OCS or GLPI database
            $out .= "<input type='checkbox' name='" . htmlescape("mactoimport[" . (int) $iterator . "][" . $value . "]") . "' " .
                 ($check == "all" ? "checked" : "") . ">\n";

            $out .= "</td>\n";
            return $out;
        } else {
            if (!empty($linkto)) {
                $out .= "<a href=\"" . htmlescape($linkto . "?" . http_build_query(['ip' => (string) $id, 'status' => (string) $type])) . "\">";
            }

            $out .= $value;
            if (!empty($linkto)) {
                $out .= "</a>";
            }

            $out .= "</td>\n";
            return $out;
        }
    }

    /**
     * @param $target
     */
    public static function checkBox($target)
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

    public static function getKnownMacAdresseFromGlpi()
    {
        global $DB;
        $macAdresses = [];
        foreach ($DB->request([
            'SELECT' => 'macaddress',
            'FROM'   => self::getTable(),
        ]) as $res) {
            $macAdresses[] = $res["macaddress"];
        }
        return $macAdresses;
    }


    /**
     * this function delete datas on an certain mac
     *
     * @param type $plugin_ocsinventoryng_ocsservers_id string
     * @param type $macAdresses array
     * @param string $subnet subnet (NETID) of the list the MAC addresses were chosen from
     *
     * @return void
     */
    public static function deleteMACFromOCS($plugin_ocsinventoryng_ocsservers_id, $macAdresses, string $subnet)
    {
        if (filter_var($subnet, FILTER_VALIDATE_IP) === false) {
            return;
        }

        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();

        // MAC addresses posted by the form: valid ones only, bound by the query builder
        $macs = array_filter(self::getMacAdressKeyVal($macAdresses), [self::class, 'isMac']);

        foreach ($macs as $key => $mac) {
            // Only the MAC addresses discovered on the subnet shown, not those of other subnets
            $in_subnet = $DBOCS->request([
                'COUNT' => 'cpt',
                'FROM'  => 'netmap',
                'WHERE' => ['MAC' => $mac, 'NETID' => $subnet],
            ])->current();
            if (empty($in_subnet['cpt'])) {
                continue;
            }
            $DBOCS->delete('netmap', ['MAC' => $mac, 'NETID' => $subnet]);
            $DBOCS->delete('network_devices', ['MACADDR' => $mac]);
        }
    }

    /**
     * this function delete link on an certain mac
     *
     * @param $id_links
     *
     * @return void
     * @internal param type $plugin_ocsinventoryng_ocsservers_id string
     * @internal param type $ids array
     */
    public static function deleteLink($id_links)
    {

        $ids = self::getMacAdressKeyVal($id_links);

        foreach ($ids as $key => $id) {
            // A link of the current server, whose item the user may update
            $disc = new self();
            if (!$disc->getFromDB((int) $id)
                || (int) $disc->fields['plugin_ocsinventoryng_ocsservers_id'] !== (int) ($_SESSION["plugin_ocsinventoryng_ocsservers_id"] ?? 0)) {
                continue;
            }
            $item = self::getHardwareItem($disc->fields['itemtype']);
            if ($item === null || !$item->can((int) $disc->fields['items_id'], UPDATE)) {
                continue;
            }
            $disc->delete(['id' => $disc->getID()], true);
        }
    }


    /**
     * @param $array
     * @param $key
     * @param $val
     *
     * @return bool
     */
    public static function findInArray($array, $key, $val)
    {
        foreach ($array as $item) {
            if (isset($item[$key]) && $item[$key] == $val) {
                return true;
            }
        }
        return false;
    }

    /**
     * this function get datas on an certain ipaddress
     *
     * @param        $ipAdress string
     * @param        $plugin_ocsinventoryng_ocsservers_id string
     * @param        $status string
     * @param array $knownMacAdresses array
     *
     * @return array
     */
    public static function getHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id, $status, $knownMacAdresses = [])
    {
        global $DB;

        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
        $query     = "";

        // The subnet comes from the request: a quoted IP address in the OCS queries
        $ipAdress = self::checkIp($ipAdress);
        $status   = self::checkStatus($status);
        $qip      = \DBmysql::quoteValue($ipAdress);

        if ($status == "imported") {
            // Links of GLPI: the query builder
            $hardware = [];
            foreach ($DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'subnet'                              => $ipAdress,
                    'plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
                ],
                'ORDER' => 'last_update',
            ]) as $res) {
                if (!isset($res["mac"]) || !self::findInArray($hardware, "mac", $res["mac"])) {
                    $hardware[] = $res;
                }
            }
            return $hardware;
        }

        if ($status == "inventoried") {
            $query = " SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain`
         FROM `hardware`
         LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id`
         WHERE `networks`.`ipsubnet`=$qip
         AND status='Up'
         GROUP BY `hardware`.`id`,`hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain`
         ORDER BY `hardware`.`lastdate`";
        } elseif ($status == "noninventoried") {
            $query = " SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name` as DNS
              FROM `netmap`
              LEFT JOIN `networks`
              ON `netmap`.`mac` =`networks`.`macaddr`
              WHERE `netmap`.`netid`=$qip
              AND (`networks`.`macaddr` IS NULL OR `networks`.`ipsubnet` <> `netmap`.`netid`)
              AND `netmap`.`mac` NOT IN ( SELECT DISTINCT(`network_devices`.`macaddr`)
              FROM `network_devices`)
              GROUP BY `netmap`.`mac`,`netmap`.`ip`,`netmap`.`mask`, `netmap`.`date`, `netmap`.`name`
              ORDER BY `netmap`.`date` DESC";
        } else {
            //group by doesn't work well
            $macAdresses = self::parseArrayToString($knownMacAdresses);
            $query       = "SELECT `network_devices`.`id`,`network_devices`.`type`,`network_devices`.`description`,`network_devices`.`user`,`netmap`.`ip`,`netmap`.`mac`,`netmap`.`mask`,`netmap`.`netid`,`netmap`.`name`,`netmap`.`date`
              FROM `network_devices`
              LEFT JOIN `netmap`
              ON `network_devices`.`macaddr`=`netmap`.`mac`
              WHERE `netmap`.`netid`=$qip
              AND `network_devices`.`MACADDR` NOT IN($macAdresses)
              GROUP BY `network_devices`.`macaddr`,`network_devices`.`id`,`network_devices`.`type`,`network_devices`.`description`,`network_devices`.`user`,`netmap`.`ip`,`netmap`.`mac`,`netmap`.`mask`,`netmap`.`netid`,`netmap`.`name`,`netmap`.`date`
              ORDER BY `netmap`.`date` DESC";
        }
        $result   = $DBOCS->doQuery($query);
        $hardware = [];

        while ($res = $DBOCS->fetchAssoc($result)) {
            if (!isset($res["mac"])) {
                $hardware[] = $res;
            } elseif (!self::findInArray($hardware, "mac", $res["mac"])) {
                $hardware[] = $res;
            }
        }
        return $hardware;
    }

    /**
     * this function load in memory the mac address constructor
     */
    /**
     * MAC prefix => manufacturer table loaded in session by loadMacConstructor(). A value
     * serialized by an older version is decoded without any object.
     *
     * @return array<string, string>
     */
    public static function getMacConstructors(): array
    {
        $macs = $_SESSION["OCS"]["IpdiscoverMacConstructors"] ?? [];
        if (is_string($macs)) {
            $macs = unserialize($macs, ['allowed_classes' => false]);
        }

        return is_array($macs) ? $macs : [];
    }

    public static function loadMacConstructor()
    {
        $macFile = PLUGIN_OCS_DIR . "/files/macManufacturers.txt";
        $result  = "";
        $macs    = [];
        if ($file = @fopen($macFile, "r")) {
            while (!feof($file)) {
                $line = fgets($file, 4096);
                if (preg_match("/^((?:[a-fA-F0-9]{2}-){2}[a-fA-F0-9]{2})\s+\(.+\)\s+(.+)\s*$/", $line, $result)) {
                    $macs[mb_strtoupper(str_replace("-", ":", $result[1]))] = $result[2];
                }
            }
            // A plain array: no serialization, so nothing to unserialize
            $_SESSION["OCS"]["IpdiscoverMacConstructors"] = $macs;
            fclose($file);
        }
    }

    /**
     * show details on a certain subnet
     *
     * @param type     $subnetsArray array
     * @param int|type $lim integer
     * @param int      $start
     * @param string   $action
     *
     * @global type    $CFG_GLPI
     */
    public static function showSubnetsDetails($subnetsArray, $lim = 0, $start = 0, $action = "import")
    {
        global $CFG_GLPI, $DB;

        $output_type = Search::HTML_OUTPUT; //0
        $return      = PLUGIN_OCS_WEBDIR . "/front/ocsng.php";
        $link        = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php";
        $action      = self::checkAction($action);
        $choice      = http_build_query(['subnetsChoice' => (string) $subnetsArray["subnetsChoice"], 'action' => $action]);
        $subnets     = $subnetsArray["subnets"];
        $row_num     = 1;

        $hardwareNetwork = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php";

        echo Html::printPager($start, count($subnets), $link, $choice);
        echo Search::showNewLine($output_type, true);
        $header_num = 1;
        echo "<table width='100%'class='tab_cadre_fixe'>\n";
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
                $name     = "unknow";
                echo Search::showNewLine($output_type, $row_num % 2);
                if ($subnets[$i]["NAME"] != "") {
                    $name = $subnets[$i]["NAME"];
                }
                echo Search::showNewLine($output_type, $row_num % 2);
                // Values of the OCS database, shown as HTML: escaped, the links built from them encoded
                $ip      = (string) $subnets[$i]["IP"];
                $ip_html = htmlescape($ip);
                $ip_url  = rawurlencode($ip);
                $link = htmlescape(PLUGIN_OCS_WEBDIR . "/front/ipdiscover.modifynetwork.php?ip=" . $ip_url);
                echo "<td class='center'><a href=\"$link\"" . Search::showItem($output_type, htmlescape((string) $name), $item_num, $row_num) . "</a></td>";
                echo Search::showItem($output_type, $ip_html, $item_num, $row_num);
                $link = $hardwareNetwork . "?ip=$ip_url&amp;status=noninventoried&amp;action=$action";
                echo "<td class='center'><a href=\"$link\"" . Search::showItem(
                    $output_type,
                    $subnets[$i]["NON_INVENTORIED"],
                    $item_num,
                    $row_num
                ) . "</a></td>";
                $link = $hardwareNetwork . "?ip=$ip_url&amp;status=inventoried&amp;action=$action";
                echo "<td class='center'><a href=\"$link\"" . Search::showItem(
                    $output_type,
                    $subnets[$i]["INVENTORIED"],
                    $item_num,
                    $row_num
                ) . "</a></td>";
                $link = $hardwareNetwork . "?ip=$ip_url&amp;status=identified&amp;action=$action";
                echo "<td class='center'><a href=\"$link\"" . Search::showItem(
                    $output_type,
                    $subnets[$i]["IDENTIFIED"],
                    $item_num,
                    $row_num
                ) . "</a></td>";
                $imported_count = (int) $DB->request([
                    'COUNT' => 'count',
                    'FROM'  => self::getTable(),
                    'WHERE' => ['subnet' => $ip],
                ])->current()['count'];
                if ($imported_count > 0) {
                    $link = $hardwareNetwork . "?ip=$ip_url&amp;status=imported&amp;action=$action";
                    echo "<td class='center'><a href=\"$link\"" . Search::showItem(
                        $output_type,
                        $imported_count,
                        $item_num,
                        $row_num
                    ) . "</a></td>";
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
     *
     * @param type       $ipAdress string
     * @param array|type $values array
     *
     * @global type      $CFG_GLPI
     */

    public static function modifyNetworkForm($ipAdress, $values = [])
    {
        global $CFG_GLPI;
        $ocsClient = new OcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();
        // The subnet comes from the request: an IP address, escaped in the page
        $ipAdress  = self::checkIp($ipAdress);
        $ip_html   = htmlescape($ipAdress);
        $ip_url    = rawurlencode($ipAdress);
        if (!empty($values) && $values["subnetChoice"] != "0"
            && (isset($_POST["Add"]) || isset($_POST["Modify"]))) {
            // A write of the OCS database: values bound by the query builder, the mask an address
            $fields = [
                'NAME' => (string) $values["subnetName"],
                'ID'   => (string) $values["subnetChoice"],
                'MASK' => self::checkIp($values["subnetMask"]),
            ];
            if (isset($_POST["Add"])) {
                $res = $OCSDB->insert('subnet', ['NETID' => $ipAdress] + $fields);
            } else {
                $res = $OCSDB->update('subnet', $fields, ['NETID' => $ipAdress]);
            }
            if ($res) {
                $link = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?subnetsChoice=2"; //2 is for the known subnets
                Html::redirect($link);
            }
        } else {
            echo "<div class='center'><table class='tab_cadre' width='60%'>";
            echo "<tr class='tab_bg_2'><th colspan='4'>" . __('Modify Subnet', 'ocsinventoryng') . "</th></tr>\n";
            $res    = $OCSDB->request([
                'FROM'  => 'subnet',
                'WHERE' => ['NETID' => $ipAdress],
                'LIMIT' => 1,
            ])->current();
            $target = htmlescape(PLUGIN_OCS_WEBDIR . "/front/ipdiscover.modifynetwork.php?ip=" . $ip_url);

            echo "<form name=\"idSelection\" action=\"" . $target . "\" method='post'>";
            echo "<tr class='tab_bg_2' ><td class='center' >" . __('Subnet Name', 'ocsinventoryng') . "</td>";
            //this is for the identified subnets
            if ($res) {
                $n       = $res["NAME"];
                $m       = $res["MASK"];
                $idValue = $res["ID"];
                echo "<td> ";
                echo Html::input('subnetName', ['type'     => 'text',
                    'value'    => $n,
                    'required' => 'required']);
                echo "</td></tr>";
                echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
                echo "<td>";
                $sbnts = [Dropdown::EMPTY_VALUE];
                self::getAllSubnetsID($sbnts);
                Dropdown::showFromArray('subnetChoice', $sbnts, ['value' => $idValue]);
                echo "</td>";
                echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";

                echo "<td class=''>" . $ip_html . "</td></tr>";
                echo "<tr class='tab_bg_2'><td class='center'>" . __('Subnet mask') . "</td>";
                echo "<td>";
                echo Html::input('SubnetMask', ['type'     => 'text',
                    'value'    => $m,
                    'required' => 'required']);
                echo "</td></tr>";
                echo "<tr class='tab_bg_2' ><td class='center'>";
                echo Html::submit(_sx('button', 'Update'), ['name' => 'Modify', 'class' => 'btn btn-primary']);
                echo "</td>";
                Html::closeForm();
                echo "<form name=\"idSelection\" action=\"" . PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?ip=$ip_url&amp;ident=2\" method='post'>";
                echo "<td class='center'>";
                echo Html::submit(_sx('button', 'Cancel'), ['name' => 'Cancel', 'class' => 'btn btn-primary']);
                echo "</td></tr></div>";
                Html::closeForm();
            } //this is for the unidentified subnets
            else {
                echo "<td>";
                echo Html::input('subnetName', ['type'     => 'text',
                    'required' => 'required']);
                echo "</td></tr>";
                echo "<tr class='tab_bg_2'><td class='center'>" . __('Choose ID', 'ocsinventoryng') . "</td>";
                echo "<td>";
                $sbnts = [Dropdown::EMPTY_VALUE];
                self::getAllSubnetsID($sbnts);
                Dropdown::showFromArray('subnetChoice', $sbnts, ['on_change' => 'FillInput();']);
                echo "</td>";

                echo "<tr class='tab_bg_2'><td class='center'>" . __('IP address') . "</td>";
                echo "<td class=''>" . $ip_html . "</td></tr>";
                echo "<tr class='tab_bg_2'><td class='center' colspan='4'>" . __('Subnet mask') . "</td>";
                echo "<td>";
                echo Html::input('SubnetMask', ['type'     => 'text',
                    'required' => 'required']);
                echo "</td></tr>";
                echo "<tr class='tab_bg_2' ><td class='center'>";
                echo Html::submit(_sx('button', 'Add'), ['name' => 'Add', 'class' => 'btn btn-primary']);
                echo "</td>";
                Html::closeForm();
                echo "<form name=\"idSelection\" action=\"" . PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?ip=$ip_url&amp;nonident=3\" method='post'>";
                echo "<td class='center'>";
                echo Html::submit(_sx('button', 'Cancel'), ['name' => 'Cancel', 'class' => 'btn btn-primary']);
                echo "</td></tr></div>";
                Html::closeForm();
            }
        }
        echo "</table></div><br>\n";
    }

    /**
     * check if ipdiscover object must be updated or imported
     *
     * @param type  $ipDiscoveryObject array
     * @param type  $plugin_ocsinventoryng_ocsservers_id integer
     * @param       $subnet
     *
     * @return array array with the status of the import or update process
     * @throws \GlpitestSQLError
     * @global type $DB
     */
    public static function processIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet)
    {
        global $DB;
        $ocsClient = new OcsServer();
        if (!$ocsClient->checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return ['status' => OcsProcess::IPDISCOVER_NOTUPDATED];
        }
        if (!self::isMac($ipDiscoveryObject["macAdress"] ?? null)) {
            return ['status' => OcsProcess::IPDISCOVER_FAILED_IMPORT];
        }

        $datas = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'macaddress'                          => $ipDiscoveryObject["macAdress"],
                'plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
            ],
            'LIMIT' => 1,
        ])->current();
        if ($datas) {
            return self::updateIpDiscover($ipDiscoveryObject, $datas, $plugin_ocsinventoryng_ocsservers_id);
        }

        return self::importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet);
    }

    /**
     * Identify an object in OCS: add it to its network devices
     *
     * @param int    $plugin_ocsinventoryng_ocsservers_id
     * @param string $description
     * @param string $ocsType
     * @param string $mac
     */
    private static function identifyInOcs($plugin_ocsinventoryng_ocsservers_id, $description, $ocsType, $mac): void
    {
        $user = new User();
        if (!$user->getFromDB(Session::getLoginUserID())) {
            return;
        }
        $ocsClient = new OcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
        $DBOCS->insert('network_devices', [
            'description' => (string) $description,
            'type'        => (string) $ocsType,
            'macaddr'     => (string) $mac,
            'user'        => (string) $user->fields["name"],
        ]);
    }

    /**
     * import ipdiscover object
     *
     * @param type $ipDiscoveryObject array
     * @param type $plugin_ocsinventoryng_ocsservers_id integer
     * @param      $subnet
     *
     * @return array
     */
    public static function importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $subnet = '')
    {
        global $DB;

        $id       = null;
        $identify = false;
        $cfg_ocs  = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

        if (isset($ipDiscoveryObject["ocsItemType"])
          && $ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE
        ) {
            return ['status' => OcsProcess::IPDISCOVER_FAILED_IMPORT];
        }
        if ($ipDiscoveryObject["itemDescription"] == '') {
            return ['status' => OcsProcess::IPDISCOVER_FAILED_IMPORT];
        }

        if ($ipDiscoveryObject["itemName"] == "") {
            $ipDiscoveryObject["itemName"] = $ipDiscoveryObject["itemDescription"];
        }

        // The type and the entity are posted: one of the hardware types the user may create,
        // in an entity of the user
        $device = self::getHardwareItem($ipDiscoveryObject["glpiItemType"]);
        $entity = (int) $ipDiscoveryObject["entity"];
        if ($device === null
            || !$device::canCreate()
            || !Session::haveAccessToEntity($entity)
            || !self::isMac($ipDiscoveryObject["macAdress"] ?? null)) {
            return ['status' => OcsProcess::IPDISCOVER_FAILED_IMPORT];
        }
        $glpiType = $device::class;

        if (isset($ipDiscoveryObject["ocsItemType"])) {
            $identify = true;
        }

        $mac     = $ipDiscoveryObject["macAdress"];
        $ip      = filter_var($ipDiscoveryObject["itemIp"], FILTER_VALIDATE_IP) !== false ? $ipDiscoveryObject["itemIp"] : '';
        $netPort = new NetworkPort();
        $netPort->getFromDBByCrit(['mac' => $mac]);
        if (count($netPort->fields) < 1) {
            $input = [
                'is_dynamic'   => 1,
                'locations_id' => 0,
                'entities_id'  => $entity,
                'name'         => $ipDiscoveryObject["itemName"],
                'comment'      => $ipDiscoveryObject["itemDescription"]];

            $id = $device->add($input, [], $cfg_ocs['history_devices']);

            if ($id) {
                //ipdiscover link
                $DB->insert(self::getTable(), [
                    'items_id'                            => (int) $id,
                    'itemtype'                            => $glpiType,
                    'macaddress'                          => $mac,
                    'last_update'                         => date("Y-m-d H:i:s"),
                    'subnet'                              => (string) $subnet,
                    'plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
                ]);

                //add port
                $port_input = ['name'                     => $ipDiscoveryObject["itemName"] . "-" . $ip,
                    'mac'                      => $mac,
                    'items_id'                 => $id,
                    'itemtype'                 => $glpiType,
                    'instantiation_type'       => "NetworkPortEthernet",
                    "entities_id"              => $entity,
                    "NetworkName__ipaddresses" => ["-100" => $ip],
                    '_create_children'         => 1,
                    'is_deleted'               => 0];

                $netPort->add($port_input, [], $cfg_ocs['history_network']);
            }
        }

        if ($id && $identify) {
            //identify object
            self::identifyInOcs(
                $plugin_ocsinventoryng_ocsservers_id,
                $ipDiscoveryObject["itemDescription"],
                $ipDiscoveryObject["ocsItemType"],
                $mac
            );
        }

        if ($id) {
            return ['status' => OcsProcess::IPDISCOVER_IMPORTED];
        } else {
            Session::addMessageAfterRedirect($mac . " : " . __('Unable to add. an object with same MAC address already exists.', 'ocsinventoryng'), false, ERROR);
            return ['status' => OcsProcess::IPDISCOVER_FAILED_IMPORT];
        }
    }


    /**
     * link ipdiscover object
     *
     * @param type $plugin_ocsinventoryng_ocsservers_id integer
     * @param      $itemtypes
     * @param      $items_id
     * @param      $macAdresses
     * @param      $ocsItemstypes
     * @param      $itemsDescription
     * @param      $subnet
     * @param int  $identify
     *
     * @return void array
     * @throws \GlpitestSQLError
     * @internal param type $ipDiscoveryObject array
     */
    public static function linkIpDiscover($plugin_ocsinventoryng_ocsservers_id, $itemtypes, $items_id, $macAdresses, $ocsItemstypes, $itemsDescription, $subnet, $identify = 0)
    {
        global $DB;

        //ipdiscover link
        $objs = [];
        $macs = self::getMacAdressKeyVal($macAdresses);

        foreach ($itemtypes as $key => $type) {
            if (!isset($items_id[$key], $macs[$key]) || !self::isMac($macs[$key])) {
                continue;
            }
            // One of the hardware types, an item the user may update
            $device = self::getHardwareItem($type);
            $id     = (int) $items_id[$key];
            if ($device === null || !$device->can($id, UPDATE)) {
                continue;
            }
            $objs[] = ["device"      => $device,
                "id"          => $id,
                "mac"         => $macs[$key],
                "description" => ($itemsDescription[$key] ?? 0),
                "ocsType"     => ($ocsItemstypes[$key] ?? 0)];
        }

        foreach ($objs as $tab) {
            $DB->insert(self::getTable(), [
                'items_id'                            => $tab["id"],
                'itemtype'                            => $tab["device"]::class,
                'macaddress'                          => $tab["mac"],
                'last_update'                         => date("Y-m-d H:i:s"),
                'subnet'                              => (string) $subnet,
                'plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
            ]);

            $tab["device"]->update([
                'is_dynamic' => 1,
                'id'         => $tab["id"]]);

            if ($identify) {
                //identify object
                self::identifyInOcs(
                    $plugin_ocsinventoryng_ocsservers_id,
                    $tab["description"],
                    $tab["ocsType"],
                    $tab["mac"]
                );
            }
        }
    }


    /**
     * update ipdiscover object
     * @global type $DB
     *
     * @param type  $ipDiscoveryObject array
     * @param type  $datas array
     * @param type  $plugin_ocsinventoryng_ocsservers_id integer
     *
     * @return array
     */
    public static function updateIpDiscover($ipDiscoveryObject, $datas, $plugin_ocsinventoryng_ocsservers_id)
    {
        global $DB;
        $res = null;

        if (isset($ipDiscoveryObject["ocsItemType"]) && $ipDiscoveryObject["ocsItemType"] == Dropdown::EMPTY_VALUE) {
            return ['status' => OcsProcess::IPDISCOVER_NOTUPDATED];
        }
        if ($ipDiscoveryObject["itemDescription"] == '') {
            return ['status' => OcsProcess::IPDISCOVER_NOTUPDATED];
        }

        if ($ipDiscoveryObject["itemName"] == "") {
            $ipDiscoveryObject["itemName"] = $ipDiscoveryObject["itemDescription"];
        }

        // The posted type and the one of the link: hardware types only, in an entity of the
        // user, and the linked item updatable
        $itemType1 = self::getHardwareItem($ipDiscoveryObject["glpiItemType"]);
        $itemType2 = self::getHardwareItem($datas["itemtype"]);
        $entity    = (int) $ipDiscoveryObject["entity"];
        if ($itemType1 === null || $itemType2 === null
            || !Session::haveAccessToEntity($entity)
            || !$itemType2->can((int) $datas["items_id"], UPDATE)) {
            return ['status' => OcsProcess::IPDISCOVER_NOTUPDATED];
        }

        //same type of object
        //simple data update
        if ($itemType1::class === $itemType2::class) {
            $input = ["id"          => (int) $datas["items_id"],
                'entities_id' => $entity,
                'name'        => $ipDiscoveryObject["itemName"],
                'comment'     => $ipDiscoveryObject["itemDescription"]];
            $res   = $itemType2->update($input);
            $DB->update(
                self::getTable(),
                ['last_update' => date("Y-m-d H:i:s")],
                ['id' => (int) $datas["id"]]
            );
        } //not same type
        //delete old object and create a new one
        elseif ($itemType2->can((int) $datas["items_id"], DELETE)) {
            //delete old object
            $result = $itemType2->delete(["id" => (int) $datas["items_id"]]);

            if ($result) {
                //delete ipdiscoverocslink
                $DB->delete(self::getTable(), ['id' => (int) $datas["id"]]);
                //add new ipdiscover object
                $action = self::importIpDiscover($ipDiscoveryObject, $plugin_ocsinventoryng_ocsservers_id, $datas["subnet"] ?? '');
                if ($action["status"] == OcsProcess::IPDISCOVER_IMPORTED) {
                    return ['status' => OcsProcess::IPDISCOVER_SYNCHRONIZED];
                }
            }
        }
        if ($res) {
            return ['status' => OcsProcess::IPDISCOVER_SYNCHRONIZED];
        } else {
            return ['status' => OcsProcess::IPDISCOVER_NOTUPDATED];
        }
    }

    /**
     * get all the ipdiscover objects to be imported or updated
     *
     * @param type        $macAdresses array
     * @param array|type  $entities array
     * @param type        $glpiItemsTypes array
     * @param string|type $itemsNames array
     * @param type        $itemsDescription array
     * @param type        $itempsIp array
     * @param array|type  $ocsItemsTypes array
     *
     * @return array
     */

    public static function getIpDiscoverobject($macAdresses, $entities, $glpiItemsTypes, $itemsNames, $itemsDescription, $itempsIp, $ocsItemsTypes = [])
    {
        $objectToImport = [];
        $macs           = self::getMacAdressKeyVal($macAdresses);
        if (!empty($entities)) {
            foreach ($macs as $key => $mac) {
                if (!empty($ocsItemsTypes)) {
                    $objectToImport[] = ["macAdress"       => $mac,
                        "entity"          => $entities[$key],
                        "glpiItemType"    => $glpiItemsTypes[$key],
                        "ocsItemType"     => $ocsItemsTypes[$key],
                        "itemName"        => $itemsNames[$key],
                        "itemDescription" => $itemsDescription[$key],
                        "itemIp"          => $itempsIp[$key]];
                } else {
                    $objectToImport[] = ["macAdress"       => $mac,
                        "entity"          => $entities[$key],
                        "glpiItemType"    => $glpiItemsTypes[$key],
                        "itemName"        => $itemsNames[$key],
                        "itemDescription" => $itemsDescription[$key],
                        "itemIp"          => $itempsIp[$key]];
                }
            }
        } else {
            foreach ($macs as $key => $mac) {
                $ent = null;
                foreach ($_SESSION["glpiactiveentities"] as $e => $eval) {
                    $ent = $eval;
                }
                if (!empty($ocsItemsTypes)) {
                    $objectToImport[] = ["macAdress"       => $mac,
                        "entity"          => $ent,
                        "glpiItemType"    => $glpiItemsTypes[$key],
                        "ocsItemType"     => $ocsItemsTypes[$key],
                        "itemName"        => $itemsNames[$key],
                        "itemDescription" => $itemsDescription[$key],
                        "itemIp"          => $itempsIp[$key]];
                } else {
                    $objectToImport[] = ["macAdress"       => $mac,
                        "entity"          => $ent,
                        "glpiItemType"    => $glpiItemsTypes[$key],
                        "itemName"        => $itemsNames[$key],
                        "itemDescription" => $itemsDescription[$key],
                        "itemIp"          => $itempsIp[$key]];
                }
            }
        }
        return $objectToImport;
    }

    /**
     * @param $status
     *
     * @return string
     */
    public static function showPercentBar($status)
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
     * show  hardware to be identified, or identified and imported, or just the hardware with agents
     * installed on them
     *
     * @param      $hardware array
     * @param      $lim integer
     * @param int $start integer
     * @param      $ipAdress string
     * @param      $status string
     * @param          $subnet
     * @param          $action
     *
     * @global     $CFG_GLPI
     */
    public static function showHardware($hardware, $lim, $start, $ipAdress, $status, $subnet, $action)
    {
        global $CFG_GLPI, $DB;

        $output_type = Search::HTML_OUTPUT; //0
        $link        = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php";
        $return      = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php";
        // The values come from the request: checked, then encoded in the links
        $ipAdress    = self::checkIp($ipAdress);
        $status      = self::checkStatus($status);
        $action      = self::checkAction($action);
        $returnargs  = htmlescape(http_build_query(['subnetsChoice' => (string) $subnet, 'action' => $action]));
        $reload      = http_build_query(['ip' => $ipAdress, 'status' => $status, 'action' => $action]);
        $backValues  = "?" . http_build_query(['b' => [$ipAdress, $status]]);

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
        echo "<h2>" . __s('Subnet') . " " . htmlescape((string) $subnet_name) . " (" . htmlescape($ipAdress) . ") - " . htmlescape($status_name);
        echo "&nbsp;";
        $refresh = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php?" . $reload;
        Html::showSimpleForm(
            $refresh,
            'refresh',
            _sx('button', 'Refresh'),
            [],
            "fa-sync-alt fa-1x"
        );
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
                case "inventoried":
                    echo "<table width='100%'class='tab_cadre_fixe'>\n";
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
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["userid"]), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["name"]), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["osname"]), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["osversion"]), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["ipaddr"]), $item_num, $row_num);
                            echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["lastdate"]), $item_num, $row_num);
                            echo Search::showEndLine($output_type);
                        }
                    }
                    echo "</table>\n";
                    break;

                case "imported":
                    $target = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php" . $backValues;
                    self::checkBox($target);
                    echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
                    echo "<div class='center' style=\"width=100%\">";
                    echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'deletelink', 'class' => 'btn btn-primary']);
                    echo "</div>";
                    echo "<table width='100%'class='tab_cadre_fixe'>\n";
                    echo Search::showHeaderItem($output_type, __('Item'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Item type'), $header_num);
                    echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
                    echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Location'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Subnet'), $header_num);
                    echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
                    echo Search::showEndLine($output_type);
                    $row_num = 1;
                    for ($i = $start; $i < $lim + $start; $i++) {
                        if (isset($hardware[$i])) {
                            $row_num++;
                            $item_num = 1;
                            // A hardware type of the link, shown only to who may read the item
                            $class = self::getHardwareItem($hardware[$i]["itemtype"]);
                            if ($class === null || !$class->can((int) $hardware[$i]["items_id"], READ)) {
                                continue;
                            }
                            echo Search::showNewLine($output_type, $row_num % 2);
                            $iplist = "";
                            $ip     = new IPAddress();
                            // Update IPAddress
                            foreach ($DB->request([
                                'FROM' => 'glpi_networkports',
                                'WHERE' => ['itemtype' => $hardware[$i]["itemtype"], 'items_id' => $hardware[$i]["items_id"]]]) as $netname) {
                                foreach ($DB->request([
                                    'FROM' => 'glpi_networknames',
                                    'WHERE' => ['itemtype' => 'NetworkPort', 'items_id' => $netname['id']]]) as $dataname) {
                                    foreach ($DB->request([
                                        'FROM' => 'glpi_ipaddresses',
                                        'WHERE' => ['itemtype' => 'NetworkName', 'items_id' => $dataname['id']]]) as $data) {
                                        $ip->getFromDB($data['id']);
                                        $iplist .= htmlescape((string) $ip->getName()) . "<br>";
                                    }
                                }
                            }
                            echo Search::showItem($output_type, $class->getLink(), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape($class->getTypeName()), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["macaddress"]), $item_num, $row_num);
                            echo Search::showItem($output_type, $iplist, $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) Dropdown::getDropdownName("glpi_locations", $class->fields["locations_id"])), $item_num, $row_num);
                            echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["last_update"]), $item_num, $row_num);
                            echo Search::showItem($output_type, htmlescape((string) $hardware[$i]["subnet"]), $item_num, $row_num);
                            echo self::showItem($hardware[$i]["id"], "", "", "", true, "", $i);
                            echo Search::showEndLine($output_type);
                        }
                    }

                    echo "<tbody style=\"display:none\">";
                    echo "<tr><td>";
                    echo Html::hidden('subnet', ['value' => $ipAdress]);
                    echo "</td></tr>";
                    echo "</tbody>";
                    echo "</table>\n";
                    echo "<div class='center' style=\"width=100%\">";
                    echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'deletelink', 'class' => 'btn btn-primary']);
                    echo "</div>";
                    Html::closeForm();
                    self::checkBox($target);
                    break;

                case "noninventoried":
                    $ocsTypes       = ["id" => [Dropdown::EMPTY_VALUE], "name" => [Dropdown::EMPTY_VALUE]];
                    $link           = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php";
                    $target         = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php" . $backValues;
                    $macConstructor = "";
                    self::getOCSTypes($ocsTypes);
                    self::checkBox($target);
                    echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
                    echo "<div class='center' style=\"width=100%\">";
                    if ($action == "import") {
                        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    } else {
                        echo Html::submit(__('Link', 'ocsinventoryng'), ['name' => 'IdentifyAndLink', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    }
                    echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete', 'class' => 'btn btn-primary']);
                    echo "</div>";
                    echo "<table width='100%'class='tab_cadre_fixe'>\n";
                    echo Search::showHeaderItem($output_type, __('Date'), $header_num);
                    echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
                    echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Subnet mask'), $header_num);
                    echo Search::showHeaderItem($output_type, __('DNS', 'ocsinventoryng'), $header_num);
                    echo Search::showHeaderItem($output_type, __('Description') . "<span style='color:red'>*</span>", $header_num);
                    echo Search::showHeaderItem($output_type, __('OCS Type', 'ocsinventoryng') . "<span style='color:red'>*</span>", $header_num);
                    if ($action == "import") {
                        echo Search::showHeaderItem($output_type, __('Name'), $header_num);
                        if (Session::isMultiEntitiesMode()) {
                            echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
                        }

                        echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng') . "<span style='color:red'>*</span>", $header_num);
                    } else {
                        echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
                    }
                    echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
                    echo Search::showEndLine($output_type);
                    $row_num  = 1;
                    $ocstypes = [];
                    foreach ($ocsTypes["name"] as $items) {
                        $ocstypes[$items] = $items;
                    }
                    $itemstypes = [Dropdown::EMPTY_VALUE];
                    foreach (self::$hardwareItemTypes as $items) {
                        $dbu = new DbUtils();
                        $class              = $dbu->getItemForItemtype($items);
                        $itemstypes[$items] = $class->getTypeName();
                    }
                    for ($i = $start; $i < $lim + $start; $i++) {
                        if (isset($hardware[$i])) {
                            $row_num++;
                            echo Search::showNewLine($output_type, $row_num % 2);
                            echo self::showItem(Html::convDateTime($hardware[$i]["date"]));
                            if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                                $macs = self::getMacConstructors();
                                if (isset($macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                                    $macConstructor = $macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                                } else {
                                    $macConstructor = __("unknow");
                                }
                            }

                            $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                            echo self::showItem(htmlescape((string) $mac));
                            echo self::showItem(htmlescape((string) ($ip = $hardware[$i]["ip"])));
                            echo self::showItem(htmlescape((string) $hardware[$i]["mask"]));

                            echo self::showItem(htmlescape((string) $hardware[$i]["DNS"]));
                            echo "<td>";
                            echo Html::input("itemsdescription[" . $i . "]", ['type'     => 'text']);
                            echo "</td>";
                            echo "<td>";
                            Dropdown::showFromArray("ocsitemstype[$i]", $ocstypes);
                            echo "</td>";

                            if ($action == "import") {
                                echo "<td>";
                                echo Html::input('itemsname[' . $i . ']', ['type'     => 'text']);
                                echo "</td>";
                                if (Session::isMultiEntitiesMode()) {
                                    echo "<td>";
                                    Entity::dropdown(['name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]]);
                                    echo "</td>";
                                }

                                echo "<td>";
                                Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                                echo "</td>";
                            } else {
                                echo "<td width='10'>";

                                $mtrand = mt_rand();

                                $mynamei = "itemtype";
                                $myname  = "tolink_items[" . $i . "]";

                                $rand = Dropdown::showItemTypes($mynamei, self::$hardwareItemTypes, ['rand' => $mtrand]);

                                $p = ['itemtype'        => '__VALUE__',
                                    'entity_restrict' => $_SESSION["glpiactiveentities"],
                                    'id'              => $i,
                                    'rand'            => $rand,
                                    'myname'          => $myname];
                                //print_r($p);
                                Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", PLUGIN_OCS_WEBDIR . "/ajax/dropdownitems.php", $p);
                                echo "<span id='results_$mynamei$rand'>\n";
                                echo "</span>\n";
                                //}
                                echo "</td>";
                            }
                            echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
                            echo "<tbody style=\"display:none\">";
                            echo "<tr><td>";
                            echo Html::hidden("itemsip[" . $i . "]", ['value' => $ip]);
                            echo Html::hidden("subnet", ['value' => $ipAdress]);
                            echo "</td></tr>";
                            echo "</tbody>";
                        }
                    }
                    echo "</table>\n";
                    echo "<div class='center' style=\"width=100%\">";
                    if ($action == "import") {
                        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    } else {
                        echo Html::submit(__('Link', 'ocsinventoryng'), ['name' => 'IdentifyAndLink', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    }
                    echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete', 'class' => 'btn btn-primary']);
                    echo "</div>";
                    Html::closeForm();
                    self::checkBox($target);
                    break;

                default:
                    $link           = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php";
                    $target         = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php" . $backValues;
                    $macConstructor = "";
                    self::checkBox($target);
                    echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
                    echo "<div class='center' style=\"width=100%\">";
                    if ($action == "import") {
                        echo Html::submit(_sx('button', 'Import'), ['name' => 'Import', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    } else {
                        echo Html::submit(__('Link', 'ocsinventoryng'), ['name' => 'Link', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    }
                    echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete', 'class' => 'btn btn-primary']);
                    echo "<table width='100%'class='tab_cadre_fixe'>";
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

                        echo Search::showHeaderItem($output_type, __('GLPI Type', 'ocsinventoryng') . "<span style='color:red'>*</span>", $header_num);
                    } else {
                        echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
                    }
                    echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
                    echo Search::showEndLine($output_type);
                    $row_num    = 1;
                    $itemstypes = [Dropdown::EMPTY_VALUE];
                    foreach (self::$hardwareItemTypes as $items) {
                        $dbu = new DbUtils();
                        $class              = $dbu->getItemForItemtype($items);
                        $itemstypes[$items] = $class->getTypeName();
                    }
                    for ($i = $start; $i < $lim + $start; $i++) {
                        if (isset($hardware[$i])) {
                            $row_num++;
                            echo Search::showNewLine($output_type, $row_num % 2);
                            echo self::showItem(Html::convDateTime($hardware[$i]["date"]));
                            echo self::showItem(htmlescape((string) ($description = $hardware[$i]["description"])));
                            echo self::showItem(htmlescape((string) $hardware[$i]["type"]));
                            echo self::showItem(htmlescape((string) ($ip = $hardware[$i]["ip"])));
                            if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                                $macs = self::getMacConstructors();
                                if (isset($macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                                    $macConstructor = $macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                                } else {
                                    $macConstructor = __("unknow");
                                }
                            }

                            $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                            echo self::showItem(htmlescape((string) $mac));

                            if ($action == "import") {
                                if (Session::isMultiEntitiesMode()) {
                                    echo "<td>";
                                    Entity::dropdown(['name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]]);
                                    echo "</td>";
                                }

                                echo "<td>";
                                echo Html::input('itemsname[' . $i . ']', ['type'     => 'text']);
                                echo "</td>";
                                echo "<td>";
                                Dropdown::showFromArray("glpiitemstype[$i]", $itemstypes);
                                echo "</td>";
                            } else {
                                echo "<td width='10'>";

                                $mtrand = mt_rand();

                                $mynamei = "itemtype";
                                $myname  = "tolink_items[" . $i . "]";

                                $rand = Dropdown::showItemTypes($mynamei, self::$hardwareItemTypes, ['rand' => $mtrand]);

                                $p = ['itemtype'        => '__VALUE__',
                                    'entity_restrict' => $_SESSION["glpiactiveentities"],
                                    'id'              => $i,
                                    'rand'            => $rand,
                                    'myname'          => $myname];

                                Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", PLUGIN_OCS_WEBDIR . "/ajax/dropdownitems.php", $p);
                                echo "<span id='results_$mynamei$rand'>\n";
                                echo "</span>\n";

                                echo "</td>";
                            }
                            echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
                            echo "<tbody style=\"display:none\">";
                            echo "<tr><td>";
                            echo Html::hidden('itemsip[' . $i . ']', ['value' => $ip]);
                            echo Html::hidden('itemsdescription[' . $i . ']', ['value' => $description]);
                            echo Html::hidden('subnet', ['value' => $ipAdress]);
                            echo "</td></tr></tbody>";
                        }
                    }
                    echo "</table>";
                    echo "<div class='center' style=\"width=100%\">";
                    if ($action == "import") {
                        echo Html::submit(_sx('button', 'Import'), ['name' => 'Import', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    } else {
                        echo Html::submit(__('Link', 'ocsinventoryng'), ['name' => 'Link', 'class' => 'btn btn-primary']);
                        echo "&nbsp;";
                    }
                    echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete', 'class' => 'btn btn-primary']);
                    echo "</div>";
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
     *
     * @param $macAdresses
     *
     * @return array with the keys(positions)
     */
    public static function getMacAdressKeyVal($macAdresses)
    {
        $keys = [];
        foreach ($macAdresses as $key => $val) {
            foreach ($val as $mac => $on) {
                $keys[$key] = $mac;
            }
        }
        return $keys;
    }
}
