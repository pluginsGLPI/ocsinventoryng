/**
 * -------------------------------------------------------------------------
 * ocsinventoryng plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ocsinventoryng.
 *
 * ocsinventoryng is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ocsinventoryng is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

// Form of an OCS server (templates/ocsserver_form.html.twig): the yes/no dropdowns of class
// ocs-toggle-<name> show or hide the blocks marked data-ocs-toggled="<name>".

document.querySelectorAll('select[class*="ocs-toggle-"]').forEach((select) => {
    const name = [...select.classList].find((css_class) => css_class.startsWith('ocs-toggle-')).substring('ocs-toggle-'.length);
    const blocks = document.querySelectorAll(`[data-ocs-toggled="${name}"]`);
    const toggle = () => blocks.forEach((block) => {
        block.hidden = select.value === '0';
    });
    // Select2 triggers the jQuery change event only
    $(select).on('change', toggle);
    toggle();
});
