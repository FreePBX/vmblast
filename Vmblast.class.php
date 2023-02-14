<?php
namespace FreePBX\modules;

class Vmblast extends \FreePBX_Helpers implements \BMO
{
	const ASTERISK_SECTION = 'vmblast-grp';

	private $FreePBX;
	private $db;
	// private $currentcomponent;

	private $table_name = array(
		'main' 	 => 'vmblast',
		'groups' => 'vmblast_groups',
		'admin'  => 'admin',
	);

	private $priorityDefault = array(
		"account" 		=> '',
		// "extdisplay" 	=> "",
		"description" 	=> '',
		"audio_label" 	=> "-1",
		"password" 		=> '',
		"default_group" => '0',
		"vmblast_list" 	=> '',
		"view" 			=> 'form',
	);

	public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new \RuntimeException('Not given a FreePBX Object');
		}

		// global $currentcomponent;

		$this->FreePBX 		  	= $freepbx;
		$this->db 			  	= $freepbx->Database;
		// $this->currentcomponent = &$currentcomponent;
	}

	public function install() {}
	public function uninstall() {}

	public function doConfigPageInit($page)
	{
		$request = $_REQUEST;
		$dispnum = 'vmblast'; //used for switch on config.php
		$action	 = isset($request['action']) ? $request['action'] : '';

		//the extension we are currently displaying
		$account        = isset($request['account'])       ? $request['account']     	: $this->priorityDefault['account'];
		$extdisplay     = isset($request['extdisplay'])    ? ltrim($request['extdisplay'],'GRP-')  : (($account != '')?$account:'');
		$description    = isset($request['description'])   ? $request['description'] 	: $this->priorityDefault['description'];
		$audio_label    = isset($request['audio_label'])   ? $request['audio_label'] 	: $this->priorityDefault['audio_label'];
		$password       = isset($request['password'])      ? $request['password']    	: $this->priorityDefault['password'];
		$default_group  = isset($request['default_group']) ? $request['default_group'] 	: $this->priorityDefault['default_group'];
		$vmblast_list   = isset($request['vmblast_list'])  ? $request['vmblast_list']  	: $this->priorityDefault['vmblast_list'];
		$view			= isset($request['view'])		   ? $request['view']			: $this->priorityDefault['view'];

		// do if we are submitting a form
		if(isset($request['action']))
		{
			//check if the extension is within range for this user
			if (isset($account) && !checkRange($account))
			{
				echo sprintf("<script>javascript:alert('%s');</script>", sprintf(_("Warning! Extension %s is not allowed for your account."), $account));
			}
			else
			{
				switch($action)
				{
					case 'addGRP':	//add group
						$conflict_url = array();
						$usage_arr = $this->hook_extensions_checkUsage($account);
						if (!empty($usage_arr))
						{
							$conflict_url = $this->hook_view_displayExtensionUsageAlert($usage_arr);
						}
						else if ($this->upsert($account, $vmblast_list, $description, $audio_label, $password, $default_group))
						{
							unset($_REQUEST['view']);
							needreload();
						}
						break;

					case 'delGRP':	//del group
						$this->delete($account);
						needreload();
						break;

					case 'editGRP':	//edit group - just delete and then re-add the extension
						$this->upsert($account, $vmblast_list, $description, $audio_label, $password, $default_group, $action);
						unset($_REQUEST['view']);
						needreload();
						break;
				}
			}
		}
	}

	public function showPage($page, $params = array())
	{
		$data = array(
			"vmblast" => $this,
			'request' => $_REQUEST,
			'page' 	  => $page,
		);
		$data = array_merge($data, $params);
		switch ($page) 
		{
			case 'main':
				$data_return = load_view(__DIR__."/views/page.main.php", $data);
				break;

			case "grid":
				$data_return = load_view(__DIR__."/views/view.grid.php", $data);
			break;

			case "form":
				$data_return = load_view(__DIR__."/views/view.form.php", $data);
				break;

			default:
				$data_return = sprintf(_("Page Not Found (%s)!!!!"), $page);
		}
		return $data_return;
	}

	public function getActionBar($request)
	{
		if ('form' !== $request['view'])
		{
			return [];
		}

		if($request['display'] === 'vmblast')
		{
			$buttons = array(
				'delete' => array(
					'name' => 'delete',
					'id' => 'delete',
					'value' => _('Delete')
				),
				'submit' => array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				),
				'reset' => array(
					'name' => 'reset',
					'id' => 'reset',
					'value' => _('Reset')
				)
			);
		}
		if (empty($request['extdisplay'])) 
		{
			unset($buttons['delete']);
		}
		return $buttons;
	}

	public function getRightNav($request, $params = array())
	{
		$data_return = "";
		$data = array(
			"vmblast" => $this,
			"request" => $request,
		);
		$data = array_merge($data, $params);
		switch($request['view'])
		{
			case 'form':
				$data_return = load_view(__DIR__.'/views/rnav.php', $data);
			break;
			default:
				//No show Nav
				
			break;
		}
		return $data_return;
	}

	public function ajaxRequest($req, &$setting)
	{
		// ** Allow remote consultation with Postman **
		// ********************************************
		// $setting['authenticate'] = false;
		// $setting['allowremote'] = true;
		// return true;
		// ********************************************
		switch($req) {
			case 'getGrid':
			case 'delGRP':
			case 'setDefaultGRP':
				return true;
			default:
				return false;
		}
	}

	public function ajaxHandler()
	{
		$command = isset($_REQUEST['command']) ? trim($_REQUEST['command']) : '';
		switch($command)
		{
			case 'getGrid':
				$default_group = $this->getDefault();
				$ret = array();
				$groups = $this->listVMBlast();
				$groups = is_array($groups) ? $groups : array();
				foreach ($groups as $k => $v)
				{
					$ret[] = array(
						'extension'   => $v[0],
						'description' => $v[1],
						'default' 	  => ($default_group == $v[0]) ? true : false
					);
				}
				$retrun_data = $ret;
				break;

			case 'delGRP':
				$account = isset($_REQUEST['account']) ? $_REQUEST['account'] : $this->priorityDefault['account'];
				if (empty($account))
				{
					$retrun_data = array("status" => false, "message" => _("Necessary data is missing!"));
				}
				else
				{
					$this->delete($account);
					needreload();
					$retrun_data = array("status" => true, "needreload" => true, "message" => _("Successfully Removed"));
				}
				break;

			case 'setDefaultGRP':
				$account = isset($_REQUEST['account']) ? $_REQUEST['account'] : $this->priorityDefault['account'];
				if (empty($account))
				{
					$retrun_data = array("status" => false, "message" => _("Necessary data is missing!"));
				}
				else
				{
					$this->clearDefaultGroup();
					$this->setDefaultGroup($account);
					needreload();
					$retrun_data = array("status" => true, "needreload" => true, "message" => _("Change Successfully"));
				}
				break;

			default:
				$retrun_data = array("status" => false, "message" => _("Command not found!"), "command" => $command);
		}
		return $retrun_data;
	}

	public function listVMBlast()
	{
		$sql = sprintf("SELECT grpnum, description FROM %s ORDER BY grpnum", $this->table_name['main']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchall(\PDO::FETCH_ASSOC);
		foreach ($results as $result)
		{
			if (isset($result['grpnum']) && checkRange($result['grpnum']))
			{
				$grps[] = array($result['grpnum'], $result['description']);
			}
		}
		
		return isset($grps) ? $grps : null;
	}

	public function getDefault()
	{
		$sql = sprintf("SELECT value FROM %s WHERE variable='default_vmblast_grp' LIMIT 1", $this->table_name['admin']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		return $stmt->fetchColumn();
	}

	public function setDefaultGroup($id)
	{
		$sql = sprintf("REPLACE INTO %s (`variable`, `value`) VALUES ('default_vmblast_grp', :grpnum)", $this->table_name['admin']);
		$this->db->prepare($sql)->execute([':grpnum' => $id]);
		return $this;
	}

	public function clearDefaultGroup($id = '')
	{
		$vars = [];
		$sql = sprintf("DELETE FROM %s WHERE variable = 'default_vmblast_grp'", $this->table_name['admin']);
		if(!empty($id))
		{
			$sql .= " AND value = :grpnum";
			$vars = [':grpnum' => $id];
		}
		$this->db->prepare($sql)->execute($vars);
		return $this;
	}

	public function checkDefault($extension)
	{
		$sql = sprintf("SELECT count(*) FROM %s WHERE ext = ':extension' AND grpnum = (SELECT value FROM %s WHERE variable = 'default_vmblast_grp' limit 1)", $this->table_name['groups'], $this->table_name['admin']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':extension' => $extension]);
		$results = $stmt->fetch(\PDO::FETCH_ASSOC);
		return ($results['total'] == 0 ? 0 : 1);
	}

	public function upsert($grpnum, $grplist, $description, $audio_label= -1, $password = '', $default_group=0, $action='')
	{
		$xtns = $grplist;
		if(!is_array($grplist))
		{
			$xtns = explode("\n", $grplist);
		}

		if ($action == 'editGRP')
		{
			$sql = sprintf("DELETE FROM %s WHERE grpnum = :grpnum", $this->table_name['groups']);
			$this->db->prepare($sql)->execute([':grpnum' => $grpnum]);
		}

		$sql = sprintf('REPLACE INTO %s (grpnum, ext) values (:grpnum,:ext)', $this->table_name['groups']);
		$stmt = $this->db->prepare($sql);
		foreach ($xtns as $key => $value)
		{
			$stmt->execute([':grpnum' => $grpnum, 'ext' => trim($value)]);
		}
		$sql = sprintf('REPLACE INTO %s (grpnum, description, audio_label, password) VALUES (:grpnum, :description, :audio_label, :password)', $this->table_name['main']);
		$stmt = $this->db->Prepare($sql)->execute([
			':grpnum' 		=> $grpnum,
			':description' 	=> $description,
			':audio_label' 	=> $audio_label,
			':password' 	=> $password
		]);

		if ($default_group)
		{
			$this->clearDefaultGroup();
			$this->setDefaultGroup($grpnum);
		}
		else
		{
			$this->clearDefaultGroup($grpnum);
		}
		return $this;
	}

	public function delete($id)
	{
		$sqls = array (
			sprintf("DELETE FROM %s WHERE grpnum = :grpnum", $this->table_name['main']),
			sprintf("DELETE FROM %s WHERE grpnum = :grpnum", $this->table_name['groups']),
		);

		foreach ($sqls as $sql)
		{
			$this->db->prepare($sql)->execute([':grpnum' => $id]);
		}

		$this->clearDefaultGroup($id);
		return $this;
	}



	// ** Start: Dialplan hooks **
	public function myDialplanHooks()
	{
		return true;
	}

	public function doDialplanHook(&$ext, $engine, $priority)
	{
		if ($engine != "asterisk") { return; }

		$contextname = self::ASTERISK_SECTION;
		$ext->addInclude('from-internal-additional', $contextname);
				
		$vmlist = $this->listVMBlast();
	
		if (function_exists('recordings_list'))
		{
			$recordings_installed = true;
			$got_recordings 	  = false;
		}
		else
		{
			$recordings_installed = false;
		}
	
		if (is_array($vmlist))
		{
			foreach($vmlist as $item)
			{
				$grpnum = ltrim($item['0']);
				$grp = $this->vmblast_get($grpnum);
				$grplist = $grp['grplist'];
				$ext->add($contextname, $grpnum, '', new \ext_macro('user-callerid'));
				$ext->add($contextname, $grpnum, '', new \ext_answer(''));
				$ext->add($contextname, $grpnum, '', new \ext_wait('1'));

				if (isset($grp['password']) && trim($grp['password']) != "" && ctype_digit(trim($grp['password']))) {
					$ext->add($contextname, $grpnum, '', new \ext_authenticate($grp['password']));
				}

				$ext->add($contextname, $grpnum, '', new \ext_setvar('GRPLIST',''));
				foreach ($grplist as $exten) {
					$ext->add($contextname, $grpnum, '', new \ext_macro('get-vmcontext',$exten));
					$ext->add($contextname, $grpnum, '', new \ext_setvar('GRPLIST','${GRPLIST}&'.$exten.'@${VMCONTEXT}'));
				}

				// Add a message and confirmation so they know what group they are in
				//
				if ($grp['audio_label'] == -2) {
					$ext->add($contextname, $grpnum, '', new \ext_goto('1','1','app-vmblast'));
				} elseif ($grp['audio_label'] == -1 || !$recordings_installed) {
					$ext->add($contextname, $grpnum, '', new \ext_setvar('DIGITS',$grpnum));
					$ext->add($contextname, $grpnum, '', new \ext_goto('digits','vmblast','app-vmblast'));
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
						$ext->add($contextname, $grpnum, '', new \ext_setvar('VMBMSG',$recording_hash[$grp['audio_label']]));
						$ext->add($contextname, $grpnum, '', new \ext_goto('msg','vmblast','app-vmblast'));
					} else {
						$ext->add($contextname, $grpnum, '', new \ext_setvar('DIGITS',$grpnum));
						$ext->add($contextname, $grpnum, '', new \ext_goto('digits','vmblast','app-vmblast'));
					}
				}
			}

			$contextname = 'app-vmblast';
			$ext->add($contextname, 'vmblast', 'digits', new \ext_execif('$["${DIGITS}" != ""]','SayDigits','${DIGITS}'));
			$ext->add($contextname, 'vmblast', 'msg', new \ext_execif('$["${VMBMSG}" != ""]','Background','${VMBMSG}'));
			$ext->add($contextname, 'vmblast', '', new \ext_background('if-correct-press&digits/1'));
			$ext->add($contextname, 'vmblast', '', new \ext_waitexten('20'));
			$ext->add($contextname, 'vmblast', '', new \ext_playback('sorry-youre-having-problems&goodbye'));
			$ext->add($contextname, 'vmblast', '', new \ext_hangup(''));

			$ext->add($contextname, '1', '', new \ext_vm('${GRPLIST:1},s'));
			$ext->add($contextname, '1', '', new \ext_hangup(''));
		}
	}
	// ** End: Dialplan hooks **


	// ** Start: Destinations hooks **
	public function getDest($exten)
	{
		return sprintf('%s,%s,1', self::ASTERISK_SECTION, $exten);
	}

	public function destinations()
	{
		$extens = array();
		foreach($this->listVMBlast() as $result)
		{
			$thisgrp = $this->vmblast_get(ltrim($result['0']));
			$extens[] = array(
				'destination' => $this->getDest(ltrim($result['0'])),
				'description' => sprintf('%s <%s>', $thisgrp['description'], ltrim($result['0']))
			);
		}
		return empty($extens) ? null : $extens;
	}

	public function destinations_getdestinfo($dest)
	{
		$srt_section = sprintf("%s,", self::ASTERISK_SECTION);
		if (substr(trim($dest),0, strlen($srt_section)) == $srt_section)
		{
			$grp  = explode(',',$dest);
			$grp  = $grp[1];
			$info = $this->destinations_format_params($grp);
			if (! empty($info))
			{
				return array(
					'description' => $info['description'],
					'edit_url' 	  => $info['edit_url'],
				);
			}
			return array();
		}
		return false;
	}

	public function destinations_check($dest=true)
	{
		$destlist = array();
		if (is_array($dest) && empty($dest)) { return $destlist; }
		foreach ($this->listVMBlast() as $result )
		{
			$grp  = ltrim($result['0']);
			$info = $this->destinations_format_params($grp);

			$destlist[] = array(
				'dest' 		  => $info['dest'],
				'description' => $info['description'],
				'edit_url' 	  => $info['edit_url'],
			);
		}
		return $destlist;
	}

	private function destinations_format_params($dest)
	{
		$info 	 = array();
		$thisgrp = $this->vmblast_get($dest);
		if (! empty($thisgrp))
		{
			$info = array(
				'dest' 		  => $this->getDest($dest),
				'description' => sprintf(_("Voicemail Group %s: %s"), $dest, $thisgrp['description']),
				'edit_url' 	  => sprintf('config.php?display=vmblast&view=form&extdisplay=GRP-%s', urlencode($dest)),
			);
		}
		return $info;
	}

	public function destinations_identif($dests)
	{
		if (! is_array($dests)) {
			$dests = array($dests);
		}
		$return_data = array();
		foreach ($dests as $target)
		{
			$info = $this->destinations_getdestinfo($target);
			if (!empty($info))
			{
				$return_data[$target] = $info;
			}
		}
		return $return_data;
	}

	public function vmblast_get($grpnum)
	{
		//TODO: Query SQL left join????

		$sql = sprintf("SELECT grpnum, description, audio_label, password FROM %s WHERE grpnum = :grpnum", $this->table_name['main']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':grpnum' => $grpnum]);
		$results = $stmt->fetch(\PDO::FETCH_ASSOC);

		$sql = sprintf("SELECT ext FROM %s WHERE grpnum = :grpnum", $this->table_name['groups']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':grpnum' => $grpnum]);
		$grplist = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

		if ($grplist === false)
		{
			die_freepbx("Error selecting from vmblast_groups table");
			// die_freepbx($grplist->getDebugInfo()."<br><br>".'selecting from vmblast_groups table');
		}
		$results['grplist'] = $grplist;

		$sql = sprintf("SELECT * FROM %s WHERE variable = 'default_vmblast_grp' AND value = :grpnum", $this->table_name['admin']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':grpnum' => $grpnum]);
		$default_group = $stmt->fetch(\PDO::FETCH_ASSOC);

    	$results['default_group'] = empty($default_group) ? 0 : $default_group['value'];
		
		return $results;
	}
	// ** End: Destinations hooks **


	/** Start: Hook Class Externals */
	public function hook_core_users_list()
	{
		$results = $this->FreePBX->Core()->listUsers();
		if (! is_array($results))
		{
			$results = array();
		}
		return $results;
	}

	public function hook_destinations_usageArray ($exten)
	{
		return $this->FreePBX->Destinations()->destinationUsageArray($this->getdest($exten));
	}

	public function hook_extensions_checkUsage($exten)
	{
		return $this->FreePBX->Extensions()->checkUsage($exten);
	}

	public function hook_view_displayExtensionUsageAlert($usage_arr)
	{
		//Old: framework_display_extension_usage_alert()
		return $this->FreePBX->View()->displayExtensionUsageAlert($usage_arr);
	}
	/** End: Hook Class Externals */


	


	public function vmblast_check_extensions($exten = true)
	{
		$extenlist = array();
		if (is_array($exten) && empty($exten))
		{
			return $extenlist;
		}

		if (is_array($exten))
		{
			$placeholders = implode(",", array_fill(0, count($exten), "?"));
			$sql = "WHERE grpnum in ($placeholders)";
		}
		else {
			$exten = null;
		}
		
		$sql = sprintf("SELECT grpnum, description FROM %s %s ORDER BY CAST(grpnum AS UNSIGNED)", $this->table_name['main'], empty($sql) ? '' : $sql);
		$stmt = $this->db->prepare($sql);
		$stmt->execute($exten);
		$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		foreach ($results as $result)
		{
			$thisexten = $result['grpnum'];
			$extenlist[$thisexten]['description'] = sprintf(_("Voicemail Group: %s"), $result['description']);
			$extenlist[$thisexten]['status'] = 'INUSE';
			$extenlist[$thisexten]['edit_url'] = sprintf('config.php?display=vmblast&extdisplay=GRP-', urlencode($thisexten));
		}
		
		return $extenlist;
	}
}