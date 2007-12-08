CREATE TABLE IF NOT EXISTS `vmblast` 
( 
	`grpnum` INT( 11 ) NOT NULL , 
	`grplist` BLOB NOT NULL , 
	`description` VARCHAR( 35 ) NOT NULL , 
	`audio_label` INT( 11 ) NOT NULL DEFAULT -1 , 
	`password` VARCHAR( 20 ) NOT NULL , 
	PRIMARY KEY  (`grpnum`) 
); 
