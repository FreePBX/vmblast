<div id="toolbar-all">
	<a href="?display=vmblast&view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add VM Blast Group")?></a>
</div>
<table id="vmblastgrid"
	data-url="ajax.php?module=vmblast&command=getGrid"
	data-cache="false"
	data-toolbar="#toolbar-all"
	data-toggle="table"
	data-pagination="true"
	data-search="true"
	data-show-refresh="true"
	class="table table-striped">
	<thead>
		<tr>
			<th data-field="extension" data-sortable="true"><?php echo _("Group")?></th>
			<th data-field="description" data-sortable="true"><?php echo _("Description")?></th>
			<th class="text-center" data-field="default" data-formatter="defaultFormatter"><?php echo _("Default")?></th>
			<th class="text-center" data-field="actions" data-formatter="actionsFormatter"><?php echo _("Actions")?></th>
		</tr>
	</thead>
</table>