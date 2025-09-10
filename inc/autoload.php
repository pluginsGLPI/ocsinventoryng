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

class PluginOcsinventoryngAutoloader
{

    public function autoload($classname)
    {
        $dir = PLUGIN_OCS_DIR . "/inc/components/";

        if (preg_match('/^PluginOcsinventoryng(.+)$/', $classname, $matches)) {
            $filename = strtolower($matches[1]) . ".class.php";
            $file = $dir . $filename;

            if (is_readable($file)) {
                include_once($file);
            }
        }
    }

    public function register()
    {
        spl_autoload_register([$this, 'autoload'], true, true);
    }
}
