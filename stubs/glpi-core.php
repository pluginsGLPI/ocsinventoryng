<?php

/*
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

/**
 * Psalm-only stubs of the GLPI core classes extended by the plugin.
 *
 * The Psalm security scan (psalm/psalm-github-security-scan) runs in a container
 * without the GLPI core: without these declarations, Psalm crashes with
 * "Could not get class storage for commondbtm" when it resolves a parent class.
 * Never loaded at runtime.
 */

#[\AllowDynamicProperties]
class CommonGLPI {}

#[\AllowDynamicProperties]
class CommonDBTM extends CommonGLPI
{
    /** @var array<string, mixed> */
    public $fields = [];
}

#[\AllowDynamicProperties]
class CommonDropdown extends CommonDBTM {}

#[\AllowDynamicProperties]
class CommonDBChild extends CommonDBTM {}

#[\AllowDynamicProperties]
class NotificationTarget extends CommonDBChild {}

#[\AllowDynamicProperties]
class NetworkPortInstantiation extends CommonDBChild {}

#[\AllowDynamicProperties]
class Profile extends CommonDBTM {}

#[\AllowDynamicProperties]
class DBmysql {}
