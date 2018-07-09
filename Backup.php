<?php
namespace FreePBX\modules\Vmblast;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $this->addConfigs($this->FreePBX->Vmblast->dumpSettings());
  }
}