//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//

function actionsFormatter(value, row, index)
{
	let val = row['extension'];
	var html = '';
	html += '<a href="?display=vmblast&view=form&extdisplay='+val+'" ><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;'
	html += '<a href="javascript:void(0)" onclick="runDelAction('+val+')"><i class="fa fa-trash"></i></a>';
	return html;
}

function defaultFormatter(value, row, index)
{
	let val = row['extension'];
	let html = '';
	if(value)
	{
		html += '<i class="fa fa-toggle-on fa-lg"></i>';
	}
	else {
		html += '<a href="javascript:void(0)" onclick="runSetDefaultAction('+val+')"><i class="fa fa-toggle-off fa-lg"></i></a>';
	}
	return html;
}

function runDelAction(idAccount)
{
	if( typeof idAccount !== 'undefined' && idAccount )
	{
		fpbxConfirm(_("Are you sure you want to delete ("+ idAccount +")?"),
		_("YES"), _("NO"),
		function()
		{
			var post_data = {
				module	: 'vmblast',
				command	: 'delGRP',
				account	: idAccount,
			};
			$.post(window.FreePBX.ajaxurl, post_data, function(res)
			{
				if (res.message)
				{
					fpbxToast(res.message, '', (res.status ? 'success' : 'error') );
				}
				if(res.status)
				{
					$('#vmblastgrid').bootstrapTable('refresh');
					if(res.needreload) {
						showButtonReloadFreePBX();	
					}
				}
			});	
		}
		);
	}
	else
	{
		fpbxToast(_("Necessary data is missing!"), '', 'error');
	}
}

function runSetDefaultAction(idAccount)
{
	if( typeof idAccount !== 'undefined' && idAccount )
	{
		var post_data = {
			module	: 'vmblast',
			command	: 'setDefaultGRP',
			account	: idAccount,
		};
		$.post(window.FreePBX.ajaxurl, post_data, function(res)
		{
			if (res.message)
			{
				fpbxToast(res.message, '', (res.status ? 'success' : 'error') );
			}
			if(res.status)
			{
				$('#vmblastgrid').bootstrapTable('refresh');
				if(res.needreload) {
					showButtonReloadFreePBX();	
				}
			}
		});
	}
	else
	{
		fpbxToast(_("Necessary data is missing!"), '', 'error');
	}
}

function showButtonReloadFreePBX(){
	$("#button_reload").show();
}

function checkGRP(theForm) {
	var msgInvalidGrpNum = _('Invalid Group Number specified');
	var msgInvalidGrpNumStartWithZero = _('Group numbers with more than one digit cannot begin with 0');
	var msgInvalidExtList = _('Please enter an extension list.');
	var msgInvalidDescription = _('Please enter a valid Group Description');
	var msgInvalidPassword = _('Please enter a valid numeric password, only numbers are allowed');

	// form validation
	defaultEmptyOK = false;
	if (!isInteger(theForm.account.value)) {
		return warnInvalid(theForm.account, msgInvalidGrpNum);
	} else if (theForm.account.value.indexOf('0') === 0 && theForm.account.value.length > 1) {
		return warnInvalid(theForm.account, msgInvalidGrpNumStartWithZero);
	}

	defaultEmptyOK = true;
	if (!isInteger(theForm.password.value))
		return warnInvalid(theForm.password, msgInvalidPassword);

	defaultEmptyOK = false;

	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, msgInvalidDescription);

	var selected = 0;
	for (var i=0; i < theForm.xtnlist.options.length; i++) {
		if (theForm.xtnlist.options[i].selected) selected += 1;
	}
	if (selected < 1) {
    theForm.xtnlist.focus();
		alert(msgInvalidExtList);
		return false;
	}

	return true;
}

$(document).ready(function()
{
	$('#xtnlist').multiselect();
});