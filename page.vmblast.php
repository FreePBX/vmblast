<?php 
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$dispnum = 'vmblast'; //used for switch on config.php

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the extension we are currently displaying
isset($_REQUEST['extdisplay'])?$extdisplay=$_REQUEST['extdisplay']:$extdisplay='';
isset($_REQUEST['account'])?$account = $_REQUEST['account']:$account='';
isset($_REQUEST['description'])?$description = $_REQUEST['description']:$description='';

if (isset($_REQUEST["grplist"])) {
	$grplist = explode("\n",$_REQUEST["grplist"]);

	if (!$grplist) {
		$grplist = null;
	}
	
	foreach (array_keys($grplist) as $key) {
		//trim it
		$grplist[$key] = trim($grplist[$key]);
		
		// remove invalid chars
		$grplist[$key] = preg_replace("/[^0-9#*]/", "", $grplist[$key]);
		
		if ($grplist[$key] == ltrim($extdisplay,'GRP-').'#')
			$grplist[$key] = rtrim($grplist[$key],'#');
		
		// remove blanks
		if ($grplist[$key] == "") unset($grplist[$key]);
	}
	
	// check for duplicates, and re-sequence
	$grplist = array_values(array_unique($grplist));
}

// do if we are submitting a form
if(isset($_POST['action'])){
	//check if the extension is within range for this user
	if (isset($account) && !checkRange($account)){
		echo "<script>javascript:alert('". _("Warning! Extension")." ".$account." "._("is not allowed for your account").".');</script>";
	} else {
		//add group
		if ($action == 'addGRP') {
			vmblast_add($account,implode("&",$grplist),$description);
			needreload();
			redirect_standard();
		}
		
		//del group
		if ($action == 'delGRP') {
			vmblast_del($account);
			needreload();
			redirect_standard();
		}
		
		//edit group - just delete and then re-add the extension
		if ($action == 'editGRP') {
			vmblast_del($account);	
			vmblast_add($account,implode("&",$grplist),$description);
			needreload();
			redirect_standard('extdisplay');
		}
	}
}
?>
</div>

<div class="rnav"><ul>
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add VMBlast Group")?></a></li> <?php 
//get unique ring groups
$gresults = vmblast_list();

if (isset($gresults)) {
	foreach ($gresults as $gresult) {
		echo "<li><a id=\"".($extdisplay=='GRP-'.$gresult[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($dispnum)."&extdisplay=".urlencode("GRP-".$gresult[0])."\">".$gresult[1]." ({$gresult[0]})</a></li>";
	}
}
?>
</ul></div>

<div class="content">
<?php 
if ($action == 'delGRP') {
	echo '<br><h3>'._("VMBlast Group").' '.$account.' '._("deleted").'!</h3><br><br><br><br><br><br><br><br>';
} else {
	if ($extdisplay) {
		// We need to populate grplist with the existing extension list.
		$thisgrp = vmblast_get(ltrim($extdisplay,'GRP-'));
		$grpliststr = $thisgrp['grplist'];
		$grplist = explode("-", $grpliststr);
		$description = $thisgrp['description'];
		unset($grpliststr);
		unset($thisgrp);
		
		$delButton = "
			<form name=delete action=\"{$_SERVER['PHP_SELF']}\" method=POST>
				<input type=\"hidden\" name=\"display\" value=\"{$dispnum}\">
				<input type=\"hidden\" name=\"account\" value=\"".ltrim($extdisplay,'GRP-')."\">
				<input type=\"hidden\" name=\"action\" value=\"delGRP\">
				<input type=submit value=\""._("Delete Group")."\">
			</form>";
			
		echo "<h2>"._("VMBlast Group").": ".ltrim($extdisplay,'GRP-')."</h2>";
		echo "<p>".$delButton."</p>";
	} else {
		$grplist = explode("-", '');;
		$strategy = '';
		$ringing = '';

		echo "<h2>"._("Add VMBlast Group")."</h2>";
	}
	?>
			<form name="editGRP" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkGRP(editGRP);">
			<input type="hidden" name="display" value="<?php echo $dispnum?>">
			<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'editGRP' : 'addGRP'); ?>">
			<table>
			<tr><td colspan="2"><h5><?php  echo ($extdisplay ? _("Edit VMBlast Group") : _("Add VMBlast Group")) ?><hr></h5></td></tr>
			<tr>
<?php
	if ($extdisplay) { 

?>
				<input size="5" type="hidden" name="account" value="<?php  echo ltrim($extdisplay,'GRP-'); ?>">
<?php 		} else { ?>
				<td><a href="#" class="info"><?php echo _("VMBlast Number")?>:<span><?php echo _("The number users will dial to voicemail boxes in this VMBlast group")?></span></a></td>
				<td><input size="5" type="text" name="account" value="<?php  if ($gresult[0]==0) { echo "500"; } else { echo $gresult[0] + 1; } ?>"></td>
<?php 		} ?>
			</tr>
			<tr>
				<td> <a href="#" class="info"><?php echo _("Group Description:")?>:<span><?php echo _("Provide a descriptive title for this VMBlast Group.")?></span></a></td>
				<td><input size="20" maxlength="35" type="text" name="description" value="<?php echo htmlspecialchars($description); ?>"></td>
			</tr>
			<tr>
				<td valign="top"><a href="#" class="info"><?php echo _("Extension list")?>:<span><br><?php echo _("List Voicemail boxes to mass send to. One per line.")?><br></span></a></td>
				<td valign="top">
<?php
		$rows = count($grplist)+1; 
		($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows);
?>
					<textarea id="grplist" cols="15" rows="<?php  echo $rows ?>" name="grplist"><?php echo implode("\n",$grplist);?></textarea><br>
					
					<input type="submit" style="font-size:10px;" value="<?php echo _("Clean & Remove duplicates")?>" />
				</td>
	
			<tr>
			<td colspan="2"><br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6></td>		
			
			</tr>
			</table>
			</form>
<?php 		
		} //end if action == delGRP
		

?>
<script language="javascript">
<!--

function checkGRP(theForm) {
	var msgInvalidGrpNum = "<?php echo _('Invalid Group Number specified'); ?>";
	var msgInvalidGrpNumStartWithZero = "<?php echo _('Group numbers with more than one digit cannot begin with 0'); ?>";
	var msgInvalidExtList = "<?php echo _('Please enter an extension list.'); ?>";
	var msgInvalidDescription = "<?php echo _('Please enter a valid Group Description'); ?>";

	// set up the Destination stuff
	setDestinations(theForm, 1);

	// form validation
	defaultEmptyOK = false;
	if (!isInteger(theForm.account.value)) {
		return warnInvalid(theForm.account, msgInvalidGrpNum);
	} else if (theForm.account.value.indexOf('0') == 0 && theForm.account.value.length > 1) {
		return warnInvalid(theForm.account, msgInvalidGrpNumStartWithZero);
	}
	
	defaultEmptyOK = false;	
	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, msgInvalidDescription);
	
	if (isEmpty(theForm.grplist.value))
		return warnInvalid(theForm.grplist, msgInvalidExtList);

	if (!validateDestinations(theForm, 1, true))
		return false;

	return true;		
}
//-->
</script>

