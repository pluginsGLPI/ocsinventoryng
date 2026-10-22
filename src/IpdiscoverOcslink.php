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
use Glpi\Application\View\TemplateRenderer;
use Html;
use NetworkName;
use NetworkPort;
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
            array_values($array),
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

            // The import and link choices side by side
            TemplateRenderer::getInstance()->display('@ocsinventoryng/ipdiscover_menu.html.twig', [
                'choices' => [self::getSubnetChoice("import"), self::getSubnetChoice("link")],
            ]);
        }
    }

    /**
     * @param $action
     */
    public static function showSubnetSearchForm($action)
    {
        TemplateRenderer::getInstance()->display(
            '@ocsinventoryng/ipdiscover_subnet_choice.html.twig',
            self::getSubnetChoice($action),
        );
    }

    /**
     * The choice of the subnets of an IPDiscover list (templates/ipdiscover_subnet_choice.html.twig)
     *
     * @param string $action import or link
     *
     * @return array
     */
    private static function getSubnetChoice($action)
    {
        $action = self::checkAction($action);

        $tab = ['0' => Dropdown::EMPTY_VALUE,
            '1' => __('All Subnets', 'ocsinventoryng'),
            '2' => __('Known Subnets', 'ocsinventoryng'),
            '3' => __('Unknown Subnets', 'ocsinventoryng')];

        $subnets = self::getSubnets($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        self::getSubnetsID($subnets["All Subnets"], $tab);
        $_SESSION["subnets"] = $tab;

        return [
            'form_url' => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?" . http_build_query(['action' => $action]),
            'title'    => $action == "import" ? __('Import IPDiscover', 'ocsinventoryng') : __('Link IPDiscover', 'ocsinventoryng'),
            'icon'     => $action == "import" ? 'ti-download' : 'ti-link',
            // Names of subnets of the OCS database: escaped by the dropdown
            'subnets'  => $tab,
        ];
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
        global $DB;

        $action  = self::checkAction($action);
        $subnets = $subnetsArray["subnets"];
        $start   = max(0, (int) $start);

        // A link of a cell: the value of the OCS database escaped, the URL built from it encoded
        $cell = fn(string $url, $value) => "<a href=\"" . htmlescape($url) . "\">" . htmlescape((string) $value) . "</a>";

        $rows = [];
        foreach (array_slice($subnets, $start, (int) $lim) as $subnet) {
            $ip     = (string) $subnet["IP"];
            $status = fn(string $status) => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php?"
                . http_build_query(['ip' => $ip, 'status' => $status, 'action' => $action]);

            $imported_count = (int) $DB->request([
                'COUNT' => 'count',
                'FROM'  => self::getTable(),
                'WHERE' => ['subnet' => $ip],
            ])->current()['count'];

            $percent = $subnet["PERCENT"];
            $rows[]  = [
                'name'           => $cell(
                    PLUGIN_OCS_WEBDIR . "/front/ipdiscover.modifynetwork.php?" . http_build_query(['ip' => $ip]),
                    $subnet["NAME"] != "" ? $subnet["NAME"] : __('Unknown'),
                ),
                'ip'             => htmlescape($ip),
                'noninventoried' => $cell($status('noninventoried'), $subnet["NON_INVENTORIED"]),
                'inventoried'    => $cell($status('inventoried'), $subnet["INVENTORIED"]),
                'identified'     => $cell($status('identified'), $subnet["IDENTIFIED"]),
                'imported'       => $imported_count > 0 ? $cell($status('imported'), $imported_count) : '0',
                'percent'        => is_numeric($percent) && $percent >= 0 && $percent <= 100
                    ? Html::getProgressBar((float) $percent)
                    : htmlescape((string) $percent),
            ];
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => _n('Subnet', 'Subnets', 2),
            'icon'          => 'ti ti-network',
            'header_link'   => [
                'title' => __('Back'),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.php",
            ],
            'columns'       => [
                'name'           => __('Description'),
                'ip'             => __('Subnet'),
                'noninventoried' => __('Non Inventoried', 'ocsinventoryng'),
                'inventoried'    => __('Inventoried', 'ocsinventoryng'),
                'identified'     => __('Identified', 'ocsinventoryng'),
                'imported'       => __('Imported / Linked', 'ocsinventoryng'),
                'percent'        => __('Percent done'),
            ],
            // Built and escaped above
            'raw_columns'   => ['name', 'ip', 'noninventoried', 'inventoried', 'identified', 'imported', 'percent'],
            'rows'          => $rows,
            'empty_message' => __('No results found'),
            'pager'         => count($subnets) > $lim ? [
                'start'             => $start,
                'count'             => count($subnets),
                'limit'             => (int) $lim,
                'href'              => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php",
                'additional_params' => http_build_query(['subnetsChoice' => (string) $subnetsArray["subnetsChoice"], 'action' => $action]),
            ] : null,
        ]);
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
        $ocsClient = new OcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();
        // The subnet comes from the request: an IP address
        $ipAdress  = self::checkIp($ipAdress);
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
            return;
        }

        $res = $OCSDB->request([
            'FROM'  => 'subnet',
            'WHERE' => ['NETID' => $ipAdress],
            'LIMIT' => 1,
        ])->current();

        $subnet_ids = [Dropdown::EMPTY_VALUE];
        self::getAllSubnetsID($subnet_ids);

        // Values of the OCS database: escaped by the template
        TemplateRenderer::getInstance()->display('@ocsinventoryng/ipdiscover_subnet.html.twig', [
            'form_url'   => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.modifynetwork.php?" . http_build_query(['ip' => $ipAdress]),
            // Known subnets (2) or unknown subnets (3) of the IPDiscover list
            'cancel_url' => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?"
                . http_build_query(['ip' => $ipAdress] + ($res ? ['ident' => 2] : ['nonident' => 3])),
            'known'      => (bool) $res,
            'ip'         => $ipAdress,
            'name'       => (string) ($res["NAME"] ?? ''),
            'mask'       => (string) ($res["MASK"] ?? ''),
            'subnet_id'  => $res["ID"] ?? 0,
            'subnet_ids' => $subnet_ids,
        ]);
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
                $mac,
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
                    $tab["mac"],
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
                ['id' => (int) $datas["id"]],
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
        global $DB;

        // The values come from the request: checked, then encoded in the links
        $ipAdress = self::checkIp($ipAdress);
        $status   = self::checkStatus($status);
        $action   = self::checkAction($action);
        $start    = max(0, (int) $start);
        $reload   = http_build_query(['ip' => $ipAdress, 'status' => $status, 'action' => $action]);
        $target   = PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php?" . http_build_query(['b' => [$ipAdress, $status]]);

        $status_names = [
            'inventoried'    => __('Inventoried', 'ocsinventoryng'),
            'imported'       => __('Imported / Linked', 'ocsinventoryng'),
            'noninventoried' => __('Non Inventoried', 'ocsinventoryng'),
        ];
        $title = sprintf(
            '%1$s %2$s (%3$s) - %4$s',
            __('Subnet'),
            (string) self::getSubnetNamebyIP($ipAdress),
            $ipAdress,
            $status_names[$status] ?? __('Identified', 'ocsinventoryng'),
        );

        // The checkbox of a line: mactoimport[<line>][<MAC address or id>], read by front/ipdiscover.import.php
        $checkbox = fn(int $i, $value) => "<input type='checkbox' class='form-check-input ocs-row-check' name='"
            . htmlescape("mactoimport[" . $i . "][" . $value . "]") . "' checked>";
        // The MAC address and its manufacturer
        $mac_name = function (string $mac) {
            $constructor = '';
            if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                $constructor = self::getMacConstructors()[mb_strtoupper(substr($mac, 0, 8))] ?? __('Unknown');
            }
            return $constructor === '' ? $mac : sprintf('%1$s (%2$s)', $mac, $constructor);
        };
        // The dropdowns of a line, as HTML
        $capture = function (callable $display) {
            ob_start();
            $display();
            return ob_get_clean();
        };
        $glpi_types = [Dropdown::EMPTY_VALUE];
        foreach (self::$hardwareItemTypes as $itemtype) {
            $glpi_types[$itemtype] = $itemtype::getTypeName(1);
        }
        $link_dropdown = fn(int $i) => $capture(function () use ($i) {
            $rand = Dropdown::showItemTypes("itemtype", self::$hardwareItemTypes, ['rand' => mt_rand()]);
            Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "results_itemtype$rand", PLUGIN_OCS_WEBDIR . "/ajax/dropdownitems.php", [
                'itemtype'        => '__VALUE__',
                'entity_restrict' => $_SESSION["glpiactiveentities"],
                'id'              => $i,
                'rand'            => $rand,
                'myname'          => "tolink_items[" . $i . "]",
            ]);
            echo "<span id='results_itemtype$rand'></span>";
        });

        $columns     = [];
        $raw_columns = [];
        $rows        = [];
        $hidden      = ['subnet' => $ipAdress];
        $buttons     = [];
        $lines       = array_slice($hardware, $start, (int) $lim, true);

        switch ($status) {
            case "inventoried":
                // Computers with an OCS agent: read only
                $columns = [
                    'userid'    => __('User'),
                    'name'      => __('Name'),
                    'osname'    => __('System'),
                    'osversion' => __('Version of the operating system'),
                    'ipaddr'    => __('IP address'),
                    'lastdate'  => __('Last OCSNG inventory date', 'ocsinventoryng'),
                ];
                foreach ($lines as $line) {
                    $rows[] = [
                        'userid'    => (string) $line["userid"],
                        'name'      => (string) $line["name"],
                        'osname'    => (string) $line["osname"],
                        'osversion' => (string) $line["osversion"],
                        'ipaddr'    => (string) $line["ipaddr"],
                        'lastdate'  => Html::convDateTime($line["lastdate"]),
                    ];
                }
                break;

            case "imported":
                $columns = [
                    'check'       => '',
                    'item'        => __('Item'),
                    'itemtype'    => __('Item type'),
                    'macaddress'  => __('MAC address'),
                    'ips'         => __('IP address'),
                    'location'    => __('Location'),
                    'last_update' => __('Import date in GLPI', 'ocsinventoryng'),
                    'subnet'      => __('Subnet'),
                ];
                $raw_columns = ['check', 'item', 'ips'];
                foreach ($lines as $i => $line) {
                    // A hardware type of the link, shown only to who may read the item
                    $class = self::getHardwareItem($line["itemtype"]);
                    if ($class === null || !$class->can((int) $line["items_id"], READ)) {
                        continue;
                    }
                    $ips = [];
                    foreach ($DB->request([
                        'SELECT'     => ['glpi_ipaddresses.name'],
                        'FROM'       => 'glpi_ipaddresses',
                        'INNER JOIN' => [
                            'glpi_networknames' => ['ON' => [
                                'glpi_ipaddresses'  => 'items_id',
                                'glpi_networknames' => 'id',
                                ['AND' => ['glpi_ipaddresses.itemtype' => NetworkName::class]],
                            ]],
                            'glpi_networkports' => ['ON' => [
                                'glpi_networknames' => 'items_id',
                                'glpi_networkports' => 'id',
                                ['AND' => ['glpi_networknames.itemtype' => NetworkPort::class]],
                            ]],
                        ],
                        'WHERE'      => [
                            'glpi_networkports.itemtype' => $line["itemtype"],
                            'glpi_networkports.items_id' => (int) $line["items_id"],
                        ],
                    ]) as $ip) {
                        $ips[] = htmlescape((string) $ip['name']);
                    }
                    $rows[] = [
                        'check'       => $checkbox((int) $i, (int) $line["id"]),
                        // Link escaped by the core
                        'item'        => $class->getLink(),
                        'itemtype'    => $class->getTypeName(1),
                        'macaddress'  => (string) $line["macaddress"],
                        'ips'         => implode('<br>', $ips),
                        'location'    => (string) Dropdown::getDropdownName("glpi_locations", $class->fields["locations_id"]),
                        'last_update' => Html::convDateTime($line["last_update"]),
                        'subnet'      => (string) $line["subnet"],
                    ];
                }
                $buttons[] = ['name' => 'deletelink', 'title' => _sx('button', 'Delete link', 'ocsinventoryng'),
                    'icon' => 'ti-unlink', 'class' => 'btn-outline-danger', 'confirm' => __('Confirm the final deletion?')];
                break;

            default:
                // Non inventoried devices (identified by the user here) or identified ones
                $identify = $status == "noninventoried";
                $ocs_types = [];
                if ($identify) {
                    $types = ["id" => [Dropdown::EMPTY_VALUE], "name" => [Dropdown::EMPTY_VALUE]];
                    self::getOCSTypes($types);
                    foreach ($types["name"] as $type) {
                        $ocs_types[$type] = $type;
                    }
                }

                $columns = ['check' => '', 'date' => __('Date')];
                if (!$identify) {
                    $columns += ['description' => __('Description'), 'type' => __('OCS Type', 'ocsinventoryng')];
                }
                $columns += ['ip' => __('IP address'), 'mac' => __('MAC address')];
                if ($identify) {
                    $columns += [
                        'mask'        => __('Subnet mask'),
                        'dns'         => __('DNS', 'ocsinventoryng'),
                        'description' => __('Description') . ' *',
                        'ocstype'     => __('OCS Type', 'ocsinventoryng') . ' *',
                    ];
                }
                if ($action == "import") {
                    if (Session::isMultiEntitiesMode()) {
                        $columns['entity'] = Entity::getTypeName(1);
                    }
                    $columns += ['itemname' => __('Name'), 'glpitype' => __('GLPI Type', 'ocsinventoryng') . ' *'];
                } else {
                    $columns['tolink'] = __('Item to link', 'ocsinventoryng');
                }
                $raw_columns = ['check', 'description', 'ocstype', 'entity', 'itemname', 'glpitype', 'tolink'];
                if (!$identify) {
                    // The description of OCS, read only
                    $raw_columns = array_diff($raw_columns, ['description']);
                }

                foreach ($lines as $i => $line) {
                    $i   = (int) $i;
                    // Values of the OCS database: escaped by the template, the inputs by the core
                    $row = [
                        'check' => $checkbox($i, (string) $line["mac"]),
                        'date'  => Html::convDateTime($line["date"]),
                        'ip'    => (string) $line["ip"],
                        'mac'   => $mac_name((string) $line["mac"]),
                    ];
                    $hidden["itemsip[$i]"] = (string) $line["ip"];
                    if ($identify) {
                        $row['mask']        = (string) $line["mask"];
                        $row['dns']         = (string) $line["DNS"];
                        $row['description'] = Html::input("itemsdescription[$i]", ['type' => 'text']);
                        $row['ocstype']     = $capture(fn() => Dropdown::showFromArray("ocsitemstype[$i]", $ocs_types));
                    } else {
                        $row['description'] = (string) $line["description"];
                        $row['type']        = (string) $line["type"];
                        $hidden["itemsdescription[$i]"] = (string) $line["description"];
                    }
                    if ($action == "import") {
                        if (Session::isMultiEntitiesMode()) {
                            $row['entity'] = $capture(fn() => Entity::dropdown(['name' => "entities[$i]", 'entity' => $_SESSION["glpiactiveentities"]]));
                        }
                        $row['itemname'] = Html::input("itemsname[$i]", ['type' => 'text']);
                        $row['glpitype'] = $capture(fn() => Dropdown::showFromArray("glpiitemstype[$i]", $glpi_types));
                    } else {
                        $row['tolink'] = $link_dropdown($i);
                    }
                    $rows[] = $row;
                }

                $buttons[] = $action == "import"
                    ? ['name' => $identify ? 'IdentifyAndImport' : 'Import', 'title' => _sx('button', 'Import'), 'icon' => 'ti-download']
                    : ['name' => $identify ? 'IdentifyAndLink' : 'Link', 'title' => __('Link', 'ocsinventoryng'), 'icon' => 'ti-link'];
                $buttons[] = ['name' => 'delete', 'title' => _sx('button', 'Delete from OCSNG', 'ocsinventoryng'),
                    'icon' => 'ti-trash', 'class' => 'btn-outline-danger', 'confirm' => __('Confirm the final deletion?')];
                break;
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => $title,
            'icon'          => 'ti ti-network',
            'header_link'   => $subnet >= 0 ? [
                'title' => __('Back'),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.php?"
                    . http_build_query(['subnetsChoice' => (string) $subnet, 'action' => $action]),
            ] : null,
            'refresh_url'   => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php?" . $reload,
            'columns'       => $columns,
            'raw_columns'   => $raw_columns,
            'rows'          => $rows,
            'empty_message' => __('No new IPDiscover device to import', 'ocsinventoryng'),
            'form_action'   => count($buttons) ? $target : null,
            'hidden'        => $hidden,
            'buttons'       => $buttons,
            'pager'         => count($hardware) > $lim ? [
                'start'             => $start,
                'count'             => count($hardware),
                'limit'             => (int) $lim,
                'href'              => PLUGIN_OCS_WEBDIR . "/front/ipdiscover.import.php",
                'additional_params' => $reload,
            ] : null,
        ]);
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
