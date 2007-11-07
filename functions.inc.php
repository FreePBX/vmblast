<?php /* $Id: functions.inc.php 3396 2006-12-21 02:40:16Z p_lindheimer $ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function vmblast_destinations() {
	//get the list of vmblast
	$results = vmblast_list();
	
	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$thisgrp = vmblast_get(ltrim($result['0']));
				$extens[] = array('destination' => 'vmblast-grp,'.ltrim($result['0']).',1', 'description' => $thisgrp['description'].' <'.ltrim($result['0']).'>');
		}
	}
	
	if (isset($extens)) 
		return $extens;
	else
		return null;
}

/* 	Generates dialplan for vmblast We call this with retrieve_conf
*/

function vmblast_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	switch($engine) {
		case "asterisk":
			$ext->addInclude('from-internal-additional','vmblast-grp');
			$contextname = 'vmblast-grp';
			$ringlist = vmblast_list();
			if (is_array($ringlist)) {
				foreach($ringlist as $item) {
					$grpnum = ltrim($item['0']);
					$grp = vmblast_get($grpnum);
					$grplist = explode('&',$grp['grplist']);
					$ext->add($contextname, $grpnum, '', new ext_macro('user-callerid'));
					$ext->add($contextname, $grpnum, '', new ext_setvar('GRPLIST',''));
					foreach ($grplist as $exten) {
						$ext->add($contextname, $grpnum, '', new ext_macro('get-vmcontext',$exten));
						$ext->add($contextname, $grpnum, '', new ext_setvar('GRPLIST','${GRPLIST}&'.$exten.'@${VMCONTEXT}'));
					}
					$ext->add($contextname, $grpnum, '', new ext_vm('${GRPLIST:1},s'));
					$ext->add($contextname, $grpnum, '', new ext_hangup(''));
				}
			}
		break;
	}
}

function vmblast_add($grpnum,$grplist,$description) {
	$sql = "INSERT INTO vmblast (grpnum, grplist, description) VALUES (".$grpnum.", '".str_replace("'", "''", $grplist)."', '".str_replace("'", "''", $description)."')";
	$results = sql($sql);
}

function vmblast_del($grpnum) {
	$results = sql("DELETE FROM vmblast WHERE grpnum = $grpnum","query");
}

function vmblast_list() {
	$results = sql("SELECT grpnum, description FROM vmblast ORDER BY grpnum","getAll",DB_FETCHMODE_ASSOC);
	foreach ($results as $result) {
		if (isset($result['grpnum']) && checkRange($result['grpnum'])) {
			$grps[] = array($result['grpnum'], $result['description']);
		}
	}
	if (isset($grps))
		return $grps;
	else
		return null;
}

function vmblast_get($grpnum) {
	$results = sql("SELECT grpnum, grplist, description FROM vmblast WHERE grpnum = $grpnum","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}
?>
