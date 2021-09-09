<?php
namespace FreePBX\modules\Vmblast;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$this->importTables($configs['tables']);
		if(isset($configs['setdefaultgroup'])) {
			$this->FreePBX->Vmblast->setDefaultGroup($configs['setdefaultgroup']);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyDatabase($pdo);
	}
}
