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

function vmblast_getdest($exten) {
	return array("vmblast-grp,$exten,1");
}

function vmblast_getdestinfo($dest) {
	if (substr(trim($dest),0,12) == 'vmblast-grp,') {
		$grp = explode(',',$dest);
		$grp = $grp[1];
		$thisgrp = vmblast_get($grp);
		if (empty($thisgrp)) {
			return array();
		} else {
			return array('description' => 'Voicemail Group '.$grp.': '.$thisgrp['description'],
			             'edit_url' => 'config.php?display=vmblast&extdisplay=GRP-'.urlencode($grp),
								  );
		}
	} else {
		return false;
	}
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

			if (function_exists('recordings_list')) { 
				$recordings_installed = true;
				$got_recordings = false;
			} else {
				$recordings_installed = false;
			}

			if (is_array($ringlist)) {
				foreach($ringlist as $item) {
					$grpnum = ltrim($item['0']);
					$grp = vmblast_get($grpnum);
					$grplist = explode('&',$grp['grplist']);
					$ext->add($contextname, $grpnum, '', new ext_macro('user-callerid'));
					$ext->add($contextname, $grpnum, '', new ext_answer(''));
					$ext->add($contextname, $grpnum, '', new ext_wait('1'));

					if (isset($grp['password']) && trim($grp['password']) != "" && ctype_digit(trim($grp['password']))) {
						$ext->add($contextname, $grpnum, '', new ext_authenticate($grp['password']));
					}

					$ext->add($contextname, $grpnum, '', new ext_setvar('GRPLIST',''));
					foreach ($grplist as $exten) {
						$ext->add($contextname, $grpnum, '', new ext_macro('get-vmcontext',$exten));
						$ext->add($contextname, $grpnum, '', new ext_setvar('GRPLIST','${GRPLIST}&'.$exten.'@${VMCONTEXT}'));
					}

					// Add a message and confirmation so they know what group they are in
					//
					if ($grp['audio_label'] == -1 || !$recordings_installed) {
						$ext->add($contextname, $grpnum, '', new ext_setvar('DIGITS',$grpnum));
						$ext->add($contextname, $grpnum, '', new ext_goto('digits','vmblast','app-vmblast'));
					} else {
						if (!$got_recordings) {
							$recordings = recordings_list();
							$got_recordings = true;
							$recording_hash = array();
							foreach ($recordings as $recording) {
								$recording_hash[$recording[0]] = $recording[2];
							}
						}
						if (isset($recording_hash[$grp['audio_label']])) {
							$ext->add($contextname, $grpnum, '', new ext_setvar('MSG',$recording_hash[$grp['audio_label']]));
							$ext->add($contextname, $grpnum, '', new ext_goto('msg','vmblast','app-vmblast'));
						} else {
							$ext->add($contextname, $grpnum, '', new ext_setvar('DIGITS',$grpnum));
							$ext->add($contextname, $grpnum, '', new ext_goto('digits','vmblast','app-vmblast'));
						}
					}
				}
				$contextname = 'app-vmblast';
				$ext->add($contextname, 'vmblast', 'digits', new ext_execif('$["${DIGITS}" != ""]','SayDigits','${DIGITS}'));
				$ext->add($contextname, 'vmblast', 'msg', new ext_execif('$["${MSG}" != ""]','Background','${MSG}'));
				$ext->add($contextname, 'vmblast', '', new ext_background('if-correct-press&digits/1'));
				$ext->add($contextname, 'vmblast', '', new ext_waitexten('20'));
				$ext->add($contextname, 'vmblast', '', new ext_playback('sorry-youre-having-problems&goodbye'));
				$ext->add($contextname, 'vmblast', '', new ext_hangup(''));

				$ext->add($contextname, '1', '', new ext_vm('${GRPLIST:1},s'));
				$ext->add($contextname, '1', '', new ext_hangup(''));
			}
		break;
	}
}

function vmblast_check_extensions($exten=true) {
	$extenlist = array();
	if (is_array($exten) && empty($exten)) {
		return $extenlist;
	}
	$sql = "SELECT grpnum ,description FROM vmblast ";
	if (is_array($exten)) {
		$sql .= "WHERE grpnum in ('".implode("','",$exten)."')";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	foreach ($results as $result) {
		$thisexten = $result['grpnum'];
		$extenlist[$thisexten]['description'] = _("Voicemail Group: ").$result['description'];
		$extenlist[$thisexten]['status'] = 'INUSE';
		$extenlist[$thisexten]['edit_url'] = 'config.php?display=vmblast&extdisplay=GRP-'.urlencode($thisexten);
	}
	return $extenlist;
}

function vmblast_add($grpnum,$grplist,$description,$audio_label= -1, $password = '') {
	global $db;

	$xtns = explode("&",$grplist);
	foreach ($xtns as $key => $value) {
		$xtns[$key] = addslashes(trim($value));
	}
		// Sanity check input.

	$compiled = $db->prepare("INSERT INTO vmblast_groups (grpnum, ext) values ('$grpnum',?)");
	$result   = $db->executeMultiple($compiled,$xtns);
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo()."<br><br>".'error adding to vmblast_groups table');	
	}
	$sql = "INSERT INTO vmblast (grpnum, description, audio_label, password) VALUES (".$grpnum.", '".str_replace("'", "''", $description)."', '$audio_label', '".str_replace("'","''", $password)."')";
	$results = sql($sql);
}

function vmblast_del($grpnum) {
	$results = sql("DELETE FROM vmblast WHERE grpnum = '$grpnum'","query");
	$results = sql("DELETE FROM vmblast_groups WHERE grpnum = '$grpnum'","query");
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
	global $db;

	$results = sql("SELECT grpnum, description, audio_label, password FROM vmblast WHERE grpnum = '$grpnum'","getRow",DB_FETCHMODE_ASSOC);
	$grplist = $db->getCol("SELECT ext FROM vmblast_groups WHERE grpnum = '$grpnum'");
	if(DB::IsError($grplist)) {
		die_freepbx($grplist->getDebugInfo()."<br><br>".'selecting from vmblast_groups table');	
	}
	$results['grplist'] = implode('&',$grplist);
	
	return $results;
}
?>
