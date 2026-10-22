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

// Forms of an OCS server. The script is included by each form, and the forms of the tabs come
// back by Ajax: the handlers are delegated to the document and bound once (window flag), so they
// work whatever the order of the script and of the forms.
(function () {
    if (window.ocsServerFormBound) {
        return;
    }
    window.ocsServerFormBound = true;

    // The yes/no dropdowns of class ocs-toggle-<name> show or hide the blocks marked
    // data-ocs-toggled="<name>" (templates/ocsserver_form.html.twig)
    const toggleName = (select) => {
        const css_class = [...select.classList].find((name) => name.startsWith('ocs-toggle-'));
        return css_class ? css_class.substring('ocs-toggle-'.length) : null;
    };
    const applyToggle = (select) => {
        const name = toggleName(select);
        if (name === null) {
            return;
        }
        (select.closest('form') || document).querySelectorAll(`[data-ocs-toggled="${name}"]`).forEach((block) => {
            block.hidden = select.value === '0';
        });
    };

    // Select2 triggers the jQuery change event only
    $(document).on('change', 'select[class*="ocs-toggle-"]', function () {
        applyToggle(this);
    });

    // The "All" dropdown (class ocs-set-all) sets every yes/no field of its form (class ocs-yesno)
    // to its value (templates/server_options.html.twig)
    $(document).on('change', 'select.ocs-set-all', function () {
        if (this.value === '-1') {
            return;
        }
        const value = this.value;
        this.closest('form').querySelectorAll('select.ocs-yesno').forEach((field) => {
            $(field).val(value).trigger('change');
        });
    });

    // Initial state of the toggled blocks, at load and when a tab comes back
    const init = () => document.querySelectorAll('select[class*="ocs-toggle-"]').forEach(applyToggle);
    $(init);
    $(document).on('glpi.tab.loaded', init);
})();
