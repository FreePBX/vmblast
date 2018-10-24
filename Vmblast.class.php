<?php
namespace FreePBX\modules;
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
use BMO;
use FreePBX_Helpers;
use PDO;
class Vmblast extends FreePBX_Helpers implements BMO {

	public function install() {}
	public function uninstall() {}

	public function doConfigPageInit($page) {
		$request = $_REQUEST;
		$dispnum = 'vmblast'; //used for switch on config.php
		$action         = isset($request['action'])        ? $request['action']      : '';

		//the extension we are currently displaying
		$account        = isset($request['account'])       ? $request['account']     : '';
		$extdisplay     = isset($request['extdisplay'])    ? ltrim($request['extdisplay'],'GRP-')  : (($account != '')?$account:'');
		$description    = isset($request['description'])   ? $request['description'] : '';
		$audio_label    = isset($request['audio_label'])   ? $request['audio_label'] : -1;
		$password       = isset($request['password'])      ? $request['password']    : '';
		$default_group  = isset($request['default_group']) ? $request['default_group'] : '0';
		$vmblast_list   = isset($request['vmblast_list'])  ? $request['vmblast_list']  : '';
		$view			= isset($request['view'])			? $request['view']			: 'form';

		// do if we are submitting a form
		if(isset($request['action'])){
			//check if the extension is within range for this user
			if (isset($account) && !checkRange($account)){
				echo "<script>javascript:alert('". _("Warning! Extension")." ".$account." "._("is not allowed for your account").".');</script>";
			} else {
				//add group
				if ($action == 'addGRP') {

					$conflict_url = array();
					$usage_arr = framework_check_extension_usage($account);
					if (!empty($usage_arr)) {
						$conflict_url = framework_display_extension_usage_alert($usage_arr);
					} else if ($this->upsert($account,$vmblast_list,$description,$audio_label,$password,$default_group)) {
						needreload();
					}
				}

				//del group
				if ($action == 'delGRP') {
					$this->delete($account);
					needreload();
				}

				//edit group - just delete and then re-add the extension
				if ($action == 'editGRP') {
					$this->upsert($account,$vmblast_list,$description,$audio_label,$password,$default_group);
					needreload();
				}
			}
		}
	}
	public function getActionBar($request){
		if ('form' !== $request['view']) {
			return [];
		}
		
		if($request['display'] === 'vmblast'){
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
		if (empty($request['extdisplay'])) {
			unset($buttons['delete']);
		}
		return $buttons;
	}
	
	public function getRightNav($request) {
		if(isset($request['view']) && $request['view'] == 'form'){
			return load_view(__DIR__."/views/bootnav.php",array());
		}
	}
	
	public function listVMBlast(){
		$sql = "SELECT grpnum, description FROM vmblast ORDER BY grpnum";
		$stmt = $this->FreePBX->Database->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchall(PDO::FETCH_ASSOC);
		foreach ($results as $result) {
			if (isset($result['grpnum']) && checkRange($result['grpnum'])) {
				$grps[] = array($result['grpnum'], $result['description']);
			}
		}
		if (isset($grps)){
			return $grps;
		}
		return null;
	}
	
	public function ajaxRequest($command, &$setting) {
		if($command === 'getJSON'){
			return true;
		}
		return false;
	}
	public function ajaxHandler(){
		if($_GET['command'] === 'getJSON' && $_GET['jdata'] === 'grid'){
			$default_group = $this->getDefault();
			$ret = array();
			$groups = $this->listVMBlast();
			$groups = is_array($groups) ? $groups : array();
			foreach ($groups as $k => $v) {
				$default = ($default_group == $v[0]) ? true : false;
				$ret[] = array('extension' => $v[0], 'description' => $v[1], 'default' => $default);
			}
			return $ret;
		}
		return false;
	}
	public function getDefault() {
		$sql = "SELECT value FROM admin WHERE variable='default_vmblast_grp' LIMIT 1";
		$stmt = $this->FreePBX->Database->prepare($sql);
		$stmt->execute();
		return $stmt->fetchColumn();
	}

	public function setDefaultGroup($id){
		$this->FreePBX->Database->prepare("REPLACE INTO admin (`variable`, `value`) VALUES ('default_vmblast_grp', :grpnum)")
			->execute([':grpnum' => $id]);
		return $this;
	}

	public function clearDefaultGroup($id = ''){
		$vars = [];
		$sql = "DELETE FROM admin WHERE variable = 'default_vmblast_grp'";
		if(!empty($id)){
			$sql .= " AND value = :grpnum";
			$vars = [':grpnum' => $id];
		}
		$this->FreePBX->Database->prepare($sql)->execute($vars);
		return $this;
	}
	
	public function upsert($grpnum,$grplist,$description,$audio_label= -1, $password = '', $default_group=0){
		$xtns = $grplist;
		if(!is_array($grplist)) {
			$xtns = explode("\n", $grplist);
		}
		$stmt = $this->FreePBX->Database->prepare('REPLACE INTO vmblast_groups (grpnum, ext) values (:grpnum,:ext)');
		foreach ($xtns as $key => $value) {
			$stmt->execute([':grpnum' => $grpnum, 'ext' => trim($value)]);
		}
		$stmt = $this->FreePBX->Database->Prepare('REPLACE INTO vmblast (grpnum, description, audio_label, password) VALUES (:grpnum, :description, :audio_label, :password)')
			->execute([':grpnum' => $grpnum, ':description' => $description, ':audio_label' => $audio_label, ':password' => $password]);
		if ($default_group) {
			$this->clearDefaultGroup();
			$this->setDefaultGroup($grpnum);
			return $this;
		}
		$this->clearDefaultGroup($grpnum);
		return $this;
	}
	public function delete($id){
		$this->FreePBX->Database->prepare("DELETE FROM vmblast WHERE grpnum = :grpnum")->execute([':grpnum' => $id]);
		$this->FreePBX->Database->prepare("DELETE FROM vmblast_groups WHERE grpnum = :grpnum")->execute([':grpnum' => $id]);
		$this->clearDefaultGroup($id);
		return $this;
	}
	public function dumpSettings($pdo){
		return [
			'vmblast' => $pdo->query('SELECT * FROM vmblast')->fetchAll(PDO::FETCH_ASSOC),
			'vmblast_groups' => $pdo->query('SELECT * FROM vmblast_groups')->fetchAll(PDO::FETCH_ASSOC),
		];
	}
}
