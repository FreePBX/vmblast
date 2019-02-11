<?php
namespace FreePBX\modules\Vmblast;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		return $this->processData($configs);
	}
	public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
		$tables = array_flip($tables + $unknownTables);
		if (!isset($tables['vmblast'])) {
			return $this;
		}
		$configs = $this->FreePBX->Vmblast->dumpSettings($pdo);
		return $this->processData($configs);
	}
	public function processData($configs){
		$groups = [];
		foreach ($configs['vmblast_groups'] as $group) {
			$groups[$group['grpnum']][] = $group['ext'];
		}
		foreach ($configs['vmblast'] as $vmblast) {
			$grplist = isset($groups[$vmblast['grpnum']]) ? $groups[$vmblast['grpnum']] : [];
			$this->FreePBX->Vmblast->upsert($vmblast['grpnum'], $vmblast['groups'], $grplist, $vmblast['description'], $vmblast['audio_label'], $vmblast['password'], $vmblast['default_group']);
		}
		return $this;
	}
}
