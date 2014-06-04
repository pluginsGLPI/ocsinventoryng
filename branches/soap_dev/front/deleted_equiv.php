<?php
/*
 * @version $Id: HEADER 15930 2014-04-28 11:55:23 Alibert Mickael $
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
include ('../../../inc/includes.php');
	plugin_ocsinventoryng_checkRight("ocsng", "w");
	
	Html::header('OCS Inventory NG', "", "plugins", "ocsinventoryng", "deleted_equiv");
	if (empty($_SESSION["ocs_deleted_equiv"]["total"])){
		PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
	}
   	if ( $_SESSION["ocs_deleted_equiv"]["total"] != $_SESSION["ocs_deleted_equiv"]["deleted"] && $_SESSION["ocs_deleted_equiv"]["last_req"]) {
   		$count = $_SESSION["ocs_deleted_equiv"]["deleted"];
      $percent = min(100,
                     round(100*($count)/$_SESSION["ocs_deleted_equiv"]["total"],
                           0));
      PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      Html::displayProgressBar(400, $percent);
      Html::redirect($_SERVER['PHP_SELF']);
   	} else {
   		$total = $_SESSION["ocs_deleted_equiv"]["total"];
   		$_SESSION["ocs_deleted_equiv"]["total"] = 0;
   		$count = $_SESSION["ocs_deleted_equiv"]["deleted"] ;
   		$_SESSION["ocs_deleted_equiv"]["deleted"]  = 0;
		    echo "<div class='center b'>";
			echo "<table class='tab_cadre_fixe'>";
			echo "<th colspan='2'>".__('Statistics of the OCSNG link', 'ocsinventoryng');
		 	echo "</th>";
		  	echo "<tr class='tab_bg_1'><td>PC DELETED</td><td>".$count."/".$total."</td></tr>";
			 echo "</table></div>";
		     echo "<div class='center b'><br>";
   	}
	if (isset($_POST["import_ok"])) {
	   if (!isset($_GET['check'])) {
	      $_GET['check'] = 'all';
	   }
	   if (!isset($_GET['start'])) {
	      $_GET['start'] = 0;
	   }
	   if (isset($_SESSION["ocs_import"])) {
	      unset($_SESSION["ocs_import"]);
	   }
	   Html::redirect($_SERVER['PHP_SELF']);
	}
Html::footer();
?>