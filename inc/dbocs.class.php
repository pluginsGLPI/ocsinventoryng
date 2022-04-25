<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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

/// DB class to connect to a OCS server
/**
 * Class PluginOcsinventoryngDBocs
 */
class PluginOcsinventoryngDBocs extends DBmysql
{
   /**
    * Constructor
    *
    * @param int|null $dbhost
    * @param $dbuser
    * @param $dbpassword
    * @param $dbdefault
    * @internal param ID $ID of the ocs server ID
    */
   function __construct($dbhost, $dbuser, $dbpassword, $dbdefault) {

      $this->dbhost = $dbhost;
      $this->dbuser = $dbuser;
      $this->dbpassword = $dbpassword;
      $this->dbdefault = $dbdefault;
      $this->connect();
   }

   /**
    * Connect using current database settings
    * Use dbhost, dbuser, dbpassword and dbdefault
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   public function connect($choice = null)
   {
      $this->connected = false;

      // Do not trigger errors nor throw exceptions at PHP level
      // as we already extract error and log while fetching result.
      mysqli_report(MYSQLI_REPORT_OFF);

      $this->dbh = @new mysqli();
      if ($this->dbssl) {
         $this->dbh->ssl_set(
            $this->dbsslkey,
            $this->dbsslcert,
            $this->dbsslca,
            $this->dbsslcapath,
            $this->dbsslcacipher
         );
      }

      if (is_array($this->dbhost)) {
         // Round robin choice
         $i    = (isset($choice) ? $choice : mt_rand(0, count($this->dbhost) - 1));
         $host = $this->dbhost[$i];
      } else {
         $host = $this->dbhost;
      }

      $hostport = explode(":", $host);
      if (count($hostport) < 2) {
         // Host
         $this->dbh->real_connect($host, $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault);
      } else if (intval($hostport[1]) > 0) {
         // Host:port
         $this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, $hostport[1]);
      } else {
         // :Socket
         $this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, ini_get('mysqli.default_port'), $hostport[1]);
      }
      //Add for OCS
      $this->dbh->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

      if ($this->dbh->connect_error) {
         $this->connected = false;
         $this->error     = 1;
      } else if (!defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
         $this->connected = false;
         $this->error     = 2;
      } else {
         $this->setConnectionCharset();

         // force mysqlnd to return int and float types correctly (not as strings)
         $this->dbh->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

         $this->dbh->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

         $this->connected = true;

         $this->setTimezone($this->guessTimezone());
      }
   }
}
