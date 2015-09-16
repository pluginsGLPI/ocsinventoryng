<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
/// DB class to connect to a OCS server
class PluginOcsinventoryngDBocs extends DBmysql {
   /**
    * Constructor
    *
    * @param $ID ID of the ocs server ID
   **/
   function __construct($dbhost, $dbuser ,$dbpassword, $dbdefault) {

      $this->dbhost     = $dbhost;
      $this->dbuser     = $dbuser;
      $this->dbpassword = $dbpassword;
      $this->dbdefault  = $dbdefault;
 
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