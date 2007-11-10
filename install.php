<?php

global $db;

$results = $db->query("ALTER TABLE `vmblast` CHANGE `grplist` `grplist` VARCHAR( 255 ) NOT NULL");
if(DB::IsError($results)) {
	echo $results->getMessage();
	return false;
}

?>
