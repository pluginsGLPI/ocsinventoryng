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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Ocsinventoryng\IpdiscoverOcslink;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();


global $CFG_GLPI, $DB;

// Types offered by the link forms (IP discover and SNMP): the table is the one of the type,
// never taken from the request
$itemtype = (string) ($_POST['itemtype'] ?? '');
$allowed  = array_unique(array_merge(IpdiscoverOcslink::$hardwareItemTypes, SnmpOcslink::$snmptypes));
if (!in_array($itemtype, $allowed, true) || !($item = getItemForItemtype($itemtype))) {
    throw new BadRequestHttpException();
}
// Only the items the user may see
if (!$item::canView()) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}
$table = $item::getTable();

$where = [];
if ($item->isEntityAssign()) {
    // The entities of the request, limited to the active ones of the user
    // (getEntitiesRestrictCriteria() takes a given list as is): the posted ones the user may
    // reach, the active ones when none is left
    $entity = '';
    if (isset($_POST['entity_restrict']) && $_POST['entity_restrict'] !== '' && $_POST['entity_restrict'] >= 0) {
        $entity = array_values(array_intersect(
            array_map('intval', (array) $_POST['entity_restrict']),
            array_map('intval', $_SESSION['glpiactiveentities'] ?? []),
        )) ?: '';
    }
    $where += getEntitiesRestrictCriteria($table, '', $entity, $item->maybeRecursive());
}

$used = array_filter(array_map('intval', (array) ($_POST['used'] ?? [])));
if (!empty($used)) {
    $where[] = ['NOT' => [$table . '.id' => $used]];
}
if ($item->maybeDeleted()) {
    $where[$table . '.is_deleted'] = 0;
}
if ($item->maybeTemplate()) {
    $where[$table . '.is_template'] = 0;
}

$search_text = (string) ($_POST['searchText'] ?? '');
if ($search_text !== '') {
    $like   = ['LIKE', '%' . $search_text . '%'];
    $search = [$table . '.name' => $like];
    if (ctype_digit($search_text)) {
        $search[$table . '.id'] = (int) $search_text;
    }
    foreach (['contact', 'serial', 'otherserial'] as $field) {
        if ($DB->fieldExists($table, $field)) {
            $search[$table . '.' . $field] = $like;
        }
    }
    $where[] = ['OR' => $search];
}

$page       = max(1, (int) ($_POST['page'] ?? 1));
$page_limit = (int) ($_POST['page_limit'] ?? $CFG_GLPI['dropdown_max']);
$page_limit = $page_limit > 0 ? min($page_limit, 1000) : (int) $CFG_GLPI['dropdown_max'];

$iterator = $DB->request([
    'FROM'   => $table,
    'WHERE'  => $where,
    'ORDER'  => $table . '.name',
    'START'  => ($page - 1) * $page_limit,
    'LIMIT'  => $page_limit,
]);

$datas = [];

// Display first if no search
if ($page == 1 && $search_text === '') {
    $datas[] = ['id' => 0, 'text' => Dropdown::EMPTY_VALUE];
}
$count = 0;
foreach ($iterator as $data) {
    $output = $data['name'];

    if (!empty($data['contact'])) {
        $output = sprintf(__('%1$s - %2$s'), $output, $data['contact']);
    }
    if (!empty($data['serial'])) {
        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
    }
    if (!empty($data['otherserial'])) {
        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
    }

    if (empty($output) || $_SESSION['glpiis_ids_visible']) {
        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
    }

    // select2 shows the text as text
    $datas[] = ['id' => (int) $data['id'], 'text' => $output];
    $count++;
}

echo json_encode(['count' => $count, 'results' => $datas]);
