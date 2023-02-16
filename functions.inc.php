<?php /* $Id: functions.inc.php 3396 2006-12-21 02:40:16Z p_lindheimer $ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//

// TODO: There is no hook on the _redirect_standard_helper function in the view.functions.php file.
function vmblast_getdest($exten)
{
	return array(\FreePBX::Vmblast()->getDest($exten));
}

// TODO: Required does not exist Hook
function vmblast_check_extensions($exten = true)
{
    return \FreePBX::Vmblast()->vmblast_check_extensions($exten);
}

function vmblast_list() {
	return FreePBX::Vmblast()->listVMBlast();
}

?>
