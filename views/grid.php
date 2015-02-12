<?php
$gresults = vmblast_list();
$default_grp = vmblast_get_default_grp();

if (isset($gresults)) {
	foreach ($gresults as $gresult) {
		$grows .= '<tr><td><a href="?display=vmblast&view=form&extdisplay='.$gresult[0].'"><i class="fa fa-edit"></i>&nbsp;'.$gresult[0].'</td><td>'.$gresult[1].'</td><td>'.($gresult[0] == $default_grp?'<i class="fa fa-check"></i>':'<i class="fa fa-ban"></i>').'</td></tr>';
	}
}
?>
<table class="table table-striped">
<thead>
	<tr>
		<th><?php echo _("Group")?></th>
		<th><?php echo _("Description")?></th>
		<th><?php echo _("Default")?></th>
	</tr>	
</thead>
<tbody>
	<?php echo $grows ?>
</tbody>
</table>