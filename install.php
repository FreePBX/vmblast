<?php

global $db;

$results = $db->query("ALTER TABLE `vmblast` CHANGE `grplist` `grplist` VARCHAR( 255 ) NOT NULL");
if(DB::IsError($results)) {
	echo $results->getMessage();
	return false;
}

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

echo "Converting grplist from varchar 255 to blob to handle large groups..";
$sql = "ALTER TABLE `vmblast` CHANGE `grplist` `grplist` BLOB NOT NULL";
$results = $db->query($sql);
if(DB::IsError($results)) {
	echo "ERROR: failed to convert field<br />";
} else {
	echo "OK<br />";
}

?>
