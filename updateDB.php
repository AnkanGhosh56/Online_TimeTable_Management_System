<?php
	
	$prevMD5=@implode('', @file(dirname(__FILE__).'/setup.md5'));
	$thisMD5=md5(@implode('', @file("./updateDB.php")));
	if($thisMD5==$prevMD5){
		$setupAlreadyRun=true;
	}else{
		
		if(!isset($silent)){
			$silent=true;
		}

		
		setupTable('schools', "create table if not exists `schools` (   `id` INT unsigned not null auto_increment , primary key (`id`), `name` VARCHAR(40) not null ) CHARSET utf8", $silent);
		setupTable('departments', "create table if not exists `departments` (   `id` INT unsigned not null auto_increment , primary key (`id`), `name` VARCHAR(40) not null , `school` INT unsigned not null ) CHARSET utf8", $silent);
		setupIndexes('departments', array('school'));
		setupTable('class_time_table', "create table if not exists `class_time_table` (   `id` INT unsigned not null auto_increment , primary key (`id`), `day` VARCHAR(40) not null , `time_start` TIME not null , `time_end` TIME not null , `unit_code` VARCHAR(40) not null , `venue` VARCHAR(40) not null , `school` INT unsigned not null , `department` INT unsigned not null , `year_of_study` VARCHAR(40) not null ) CHARSET utf8", $silent);
		setupIndexes('class_time_table', array('school','department'));
		setupTable('exam_time_table', "create table if not exists `exam_time_table` (   `id` INT unsigned not null auto_increment , primary key (`id`), `date` DATE not null , `time_start` TIME not null , `time_end` TIME not null , `unit_code` VARCHAR(40) not null , `venue` VARCHAR(40) not null , `school` INT unsigned not null , `department` INT unsigned not null , `year_of_study` VARCHAR(40) not null ) CHARSET utf8", $silent);
		setupIndexes('exam_time_table', array('school','department'));
		setupTable('personal_time_table', "create table if not exists `personal_time_table` (   `id` INT unsigned not null auto_increment , primary key (`id`), `day` VARCHAR(40) not null , `time_start` TIME not null , `time_end` TIME not null , `activity` VARCHAR(40) not null ) CHARSET utf8", $silent);
		setupTable('student_details', "create table if not exists `student_details` (   `id` INT unsigned not null auto_increment , primary key (`id`), `full_name` VARCHAR(40) not null , `school` INT unsigned not null , `department` INT unsigned not null , `year_of_study` VARCHAR(40) not null , `reg_no` VARCHAR(40) not null , unique `reg_no_unique` (`reg_no`)) CHARSET utf8", $silent, array( "ALTER TABLE `student_details` ADD UNIQUE `reg_no_unique` (`reg_no`)"));
		setupIndexes('student_details', array('school','department'));
		setupTable('notices', "create table if not exists `notices` (   `id` INT unsigned not null auto_increment , primary key (`id`), `notice` TEXT not null , `school` INT unsigned not null , `department` INT unsigned not null , `year_of_study` VARCHAR(40) not null , `date` DATE ) CHARSET utf8", $silent);
		setupIndexes('notices', array('school','department'));


		
		if($fp=@fopen(dirname(__FILE__).'/setup.md5', 'w')){
			fwrite($fp, $thisMD5);
			fclose($fp);
		}
	}


	function setupIndexes($tableName, $arrFields){
		if(!is_array($arrFields)){
			return false;
		}

		foreach($arrFields as $fieldName){
			if(!$res=@db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")){
				continue;
			}
			if(!$row=@db_fetch_assoc($res)){
				continue;
			}
			if($row['Key']==''){
				@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
			}
		}
	}


	function setupTable($tableName, $createSQL='', $silent=true, $arrAlter=''){
		global $Translation;
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		
		if(is_array($arrAlter)){
			$matches=array();
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/", $arrAlter[0], $matches)){
				$oldTableName=$matches[1];
			}
		}

		if($res=@db_query("select count(1) from `$tableName`")){
			if($row = @db_fetch_array($res)){
				echo str_replace("<TableName>", $tableName, str_replace("<NumRecords>", $row[0],$Translation["table exists"]));
				if(is_array($arrAlter)){
					echo '<br>';
					foreach($arrAlter as $alter){
						if($alter!=''){
							echo "$alter ... ";
							if(!@db_query($alter)){
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							}else{
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				}else{
					echo $Translation["table uptodate"];
				}
			}else{
				echo str_replace("<TableName>", $tableName, $Translation["couldnt count"]);
			}
		}else{ 

			if($oldTableName!=''){ 
				if($ro=@db_query("select count(1) from `$oldTableName`")){ 
					$renameQuery=array_shift($arrAlter); 

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)){
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					}else{
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); 
				}else{ 
					setupTable($tableName, $createSQL, false); 
				}
			}else{ 
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)){
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';
				}else{
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}
		}

		echo "</div>";

		$out=ob_get_contents();
		ob_end_clean();
		if(!$silent){
			echo $out;
		}
	}
?>
