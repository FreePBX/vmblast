<?php

// TODO:
// TODO: MOVE TABLE CREATIONS INTO HERE
// TODO:

global $db;

echo "Upgrading vmblast to add audio_label field..";
$sql = "SELECT audio_label FROM vmblast";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	echo "Not Required<br />";
} else {
	$sql = "ALTER TABLE vmblast ADD audio_label INT ( 11 ) NOT NULL DEFAULT -1";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	echo "Done<br />";
}

echo "Upgrading vmblast to add password field..";
$sql = "SELECT password FROM vmblast";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { // no error... Already done
	echo "Not Required<br />";
} else {
	$sql = "ALTER TABLE vmblast ADD password VARCHAR ( 20 ) NOT NULL";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
	        die($results->getMessage());
	}
	echo "Done<br />";
}

// Drop grplist field but first pull it's data and put in new table
//
echo "Dropping grplist..";
$sql = 'SELECT grpnum, grplist FROM vmblast';
$confs = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($confs)) { 
	$list = array();
	foreach ($confs as $group) {
		$grplist = explode('&',$group['grplist']);
		foreach ($grplist as $exten) {
			$list[] = array($group['grpnum'],addslashes(trim($exten)));
		}
	}
	$compiled = $db->prepare("INSERT INTO vmblast_groups (grpnum, ext) values (?,?)");
	$result   = $db->executeMultiple($compiled, $list);
	if(DB::IsError($result)) {
		echo "error populating vmblast_groups table<br />";	
		return false;
	} else {
		echo "populated new table<br />Dropping old grplist field";
		$sql = "ALTER TABLE `vmblast` DROP `grplist`";
		$results = $db->query($sql);
		if(DB::IsError($results)) {
			echo "failed to drop field<br />";
		} else {
			echo "OK<br />";
		}
	}
} else {
	echo "Not Needed<br />";
}

?>
