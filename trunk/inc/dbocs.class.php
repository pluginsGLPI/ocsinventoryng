<?php
/*
 * @version $Id: dbmysql.class.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// DB class to connect to a OCS server
class PluginOcsinventoryngDBocs extends DBmysql {

   ///Store the id of the ocs server
   var $ocsservers_id = -1;

   /**
    * Constructor
    *
    * @param $ID ID of the ocs server ID
   **/
   function __construct($ID) {

      $this->ocsservers_id = $ID;
      $data             = PluginOcsinventoryngOcsServer::getConfig($ID);
      $this->dbhost     = $data["ocs_db_host"];
      $this->dbuser     = $data["ocs_db_user"];
      $this->dbpassword = rawurldecode($data["ocs_db_passwd"]);
      $this->dbdefault  = $data["ocs_db_name"];
      $this->dbenc      = $data["ocs_db_utf8"] ? "utf8" : "latin1";
      parent::__construct();
   }


   /**
    *
    * Get current ocs server ID
    * @return ID of the ocs server ID
   **/
   function getServerID() {
      return $this->ocsservers_id;
   }

}
?>