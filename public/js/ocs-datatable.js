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

// Lists of the computers to synchronize, import or link (OcsServer::showOcsDataTable()):
// DataTables table loaded by AJAX, rows selected by their first cell, the selected ids posted
// with the form. Everything specific to the list comes from the data-ocs-table attribute.

function ocsDataTableInit(wrapper) {
    if (wrapper.dataset.ocsTableReady === '1') {
        return;
    }
    wrapper.dataset.ocsTableReady = '1';

    const config = JSON.parse(wrapper.dataset.ocsTable);
    const loader = wrapper.querySelector('[data-ocs-loader]');
    const tableElement = wrapper.querySelector('table');
    const form = wrapper.closest('form');

    const columnDefs = [
        {targets: 0, orderable: false, searchable: false, className: 'select-checkbox'},
        {targets: config.hidden_columns, visible: false, searchable: false},
    ];

    const table = $(tableElement).DataTable({
        ajax: {url: config.load_url, type: 'POST', data: config.load_data, dataType: 'json'},
        paging: true,
        pageLength: 25,
        stateSave: true,
        processing: true,
        responsive: true,
        language: config.language,
        dom: "<'d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2'lf>rt<'d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2'ip>",
        select: {style: 'multi', selector: 'td:first-child'},
        order: [[1, 'asc']],
        columns: config.columns.map((data) => ({data: data, defaultContent: ''})),
        columnDefs: columnDefs,
        initComplete: function () {
            if (loader !== null) {
                loader.classList.add('d-none');
            }
        },
    });

    // The header checkbox selects or clears every row
    const headerCheckbox = tableElement.querySelector('thead th.select-checkbox');
    const syncHeader = function () {
        if (headerCheckbox === null) {
            return;
        }
        const all = table.rows().count() > 0
            && table.rows({selected: true}).count() === table.rows().count();
        headerCheckbox.classList.toggle('selected', all);
    };
    if (headerCheckbox !== null) {
        headerCheckbox.addEventListener('click', function () {
            if (headerCheckbox.classList.contains('selected')) {
                table.rows().deselect();
            } else {
                table.rows().select();
            }
            syncHeader();
        });
    }
    table.on('select deselect draw', syncHeader);

    // The ids of the selected rows, posted with the form
    if (form !== null) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[data-ocs-selected]').forEach((input) => input.remove());
            table.rows({selected: true}).data().each(function (row) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = config.field_name + '[]';
                input.value = row.id;
                input.dataset.ocsSelected = '1';
                form.append(input);
            });
        });
    }
}

$(function () {
    document.querySelectorAll('[data-ocs-table]').forEach(ocsDataTableInit);
});
