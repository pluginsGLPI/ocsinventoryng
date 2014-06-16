<tr class="tab_bg_1">
	<td class="center"><?= __("Connection type", "ocsinventoryng") ?></td>
	<td id="conn_type_container"><?php Dropdown::showFromArray("conn_type", $conn_type_values, array("value" => $this->fields["conn_type"])) ?></td>
</tr>

<tr class="tab_bg_1">
	<td class="center"><?= __("Name") ?></td>
	<td><input type="text" name="name" value="<?= $this->fields["name"]  ?>"></td>
	<td class="center"><?= _n("Version", "Versions", 1) ?></td>
	<td><?= $this->fields["ocs_version"] ?></td>
</tr>

<tr class="tab_bg_1">
	<td class="center"><?= __("Host", "ocsinventoryng") ?></td>
	<td><input type="text" name="ocs_db_host" value="<?=$this->fields["ocs_db_host"]  ?>"/></td>
	<td class="center"><?= __("Synchronisation method", "ocsinventoryng") ?></td>
	<td><?php Dropdown::showFromArray("use_massimport", $sync_method_values, array("value" => $this->fields["use_massimport"])) ?></td>
</tr>

<tr class="tab_bg_1 hide_if_soap" <?php if ($this->fields["conn_type"] == PluginOcsinventoryngOcsServer::CONN_TYPE_SOAP): ?>style="display:none"<?php endif ?>>
	<td class="center"><?= __("Database") ?></td>
	<td><input type="text" name="ocs_db_name" value="<?= $this->fields["ocs_db_name"] ?>"></td>
	<td class="center"><?= __("Database in UTF8", "ocsinventoryng") ?></td>
	<td><?php Dropdown::showYesNo("ocs_db_utf8", $this->fields["ocs_db_utf8"]) ?></td>
</tr>

<tr class="tab_bg_1">
	<td class="center"><?= _n("User", "Users", 1) ?></td>
	<td><input type="text" name="ocs_db_user" value="<?= $this->fields["ocs_db_user"] ?>"></td>
	<td class="center" rowspan="2"><?= __("Comments") ?></td>
	<td rowspan="2"><textarea cols="45" rows="6" name="comment" ><?= $this->fields["comment"] ?></textarea></td>
</tr>

<tr class="tab_bg_1">
	<td class="center"><?= __("Password") ?></td>
	<td>
		<input type="password" name="ocs_db_passwd" value="" autocomplete="off">
		<?php if ($ID): ?>
			<br><input type="checkbox" name="_blank_passwd">&nbsp;<?= __("Clear") ?>
		<?php endif ?>
	</td>
</tr>

<tr class="tab_bg_1"><td class="center"><?=__("Active") ?></td>
	<td><?php Dropdown::showYesNo("is_active",$this->fields["is_active"]) ?></td>
	<?php if ($ID): ?>
		<td><?= __("Last update") ?></td>
		<td><?= $this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"]) : __("Never") ?></td>
	<?php endif ?>
</tr>

<script type="text/javascript">
	var connTypeSelect = document.getElementById('conn_type_container').children[0];

	connTypeSelect.onchange = function() {
		var hideIfSoapElems = document.getElementsByClassName('hide_if_soap');

		for (var i = 0; i < hideIfSoapElems.length; i++) {
			if (connTypeSelect.value == '0') {
				// if DB
				hideIfSoapElems[i].style.display = '';
			} else {
				// if SOAP
				hideIfSoapElems[i].style.display = 'none';
			}
		}
	}
</script>