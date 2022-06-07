<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
vimport('~~modules/Users/DefaultDataPopulator.php');
vimport('~~include/PopulateComboValues.php');

class Install_InitSchema_Model {

	/**
	 * Function starts applying schema changes
	 */
	public static function initialize() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Init DB from Session vars\n", FILE_APPEND);
		$adb = PearDatabase::getInstance();
		$configParams = $_SESSION['config_file_info'];
		$adb->resetSettings($configParams['db_type'], $configParams['db_hostname'], $configParams['db_name'], $configParams['db_username'], $configParams['db_password']);
		$adb->query('SET NAMES utf8');
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Set MultiQuery Mode (requires mysqli) and import SQL dump\n", FILE_APPEND);
		$adb->database->multiQuery = true;
		$schema = file_get_contents('schema/DatabaseSchema.sql');
		$adb->pquery($schema);
		$adb->database->multiQuery = false;
		if ($adb->database->_failedQuery) {
			return $adb->database->_failedQuery;
		} else {
			require 'vtigerversion.php';
			$query = "UPDATE `vtiger_version` SET `tag_version` = ?";
			$adb->pquery($query, array($current_release_tag));
			return true;
		}
		// $adb->createTables("schema/DatabaseSchema.xml");

		// $defaultDataPopulator = new DefaultDataPopulator();
		// $defaultDataPopulator->create_tables();

		// self::createDefaultUsersAccess();

		// // create and populate combo tables
		// $combo = new PopulateComboValues();
		// $combo->create_tables();
		// $combo->create_nonpicklist_tables();

		// create_tab_data_file();
		// create_parenttab_data_file();

		// // default report population
		// vimport('~~modules/Reports/PopulateReports.php');

		// // default customview population
		// vimport('~~modules/CustomView/PopulateCustomView.php');

		// // ensure required sequences are created (adodb creates them as needed, but if
		// // creation occurs within a transaction we get problems
		// $adb->getUniqueID("vtiger_crmentity");
		// $adb->getUniqueID("vtiger_seactivityrel");
		// $adb->getUniqueID("vtiger_freetags");

		// $currencyName = $_SESSION['config_file_info']['currency_name'];
		// $currencyCode = $_SESSION['config_file_info']['currency_code'];
		// $currencySymbol = $_SESSION['config_file_info']['currency_symbol'];
		// $adb->pquery("INSERT INTO vtiger_currency_info VALUES (?,?,?,?,?,?,?,?)", array($adb->getUniqueID("vtiger_currency_info"),
					// $currencyName,$currencyCode,$currencySymbol,1,'Active','-11','0'));

		// Install_InitSchema_Model::installDefaultEventsAndWorkflows();
	}

	/**
	 * Function upgrades the schema with changes post 540 version
	 */
	public static function upgrade() {
		$migrateVersions = Migration_Module_Model::getInstance('')->getAllowedMigrationVersions();

		define('VTIGER_UPGRADE', true);
		$oldVersion = null;
		foreach($migrateVersions as $migrateVersion) {
			foreach($migrateVersion as $newVersion => $versionLabel) {
				// Not ready?	
				if ($oldVersion == null) {
					$oldVersion = $newVersion;
					break;
				}
				$oldVersion = str_replace(array('.', ' '), '', $oldVersion);
				$newVersion = str_replace(array('.', ' '), '', $newVersion);
				$filename =  "modules/Migration/schema/".$oldVersion."_to_".$newVersion.".php";
				if(is_file($filename)) {
					include($filename);
				}
				$oldVersion = $newVersion;
			}
		}
		
		//crm-now: modifications during install
		self::setCRMNOWmodifications();
	}

	/**
	 * Function creates default user's Role, Profiles
	 */
	public static function createDefaultUsersAccess() {
		global $adb;
      	$adb = PearDatabase::getInstance();
        $roleId1 = $adb->getUniqueID("vtiger_role");
		$roleId2 = $adb->getUniqueID("vtiger_role");
		$roleId3 = $adb->getUniqueID("vtiger_role");
		$roleId4 = $adb->getUniqueID("vtiger_role");
		$roleId5 = $adb->getUniqueID("vtiger_role");

		$profileId1 = $adb->getUniqueID("vtiger_profile");
		$profileId2 = $adb->getUniqueID("vtiger_profile");
		$profileId3 = $adb->getUniqueID("vtiger_profile");
		$profileId4 = $adb->getUniqueID("vtiger_profile");

		$adb->pquery("INSERT INTO vtiger_role VALUES('H".$roleId1."','Organisation','H".$roleId1."',0)", array());
        $adb->pquery("INSERT INTO vtiger_role VALUES('H".$roleId2."','CEO','H".$roleId1."::H".$roleId2."',1)", array());
        $adb->pquery("INSERT INTO vtiger_role VALUES('H".$roleId3."','Vice President','H".$roleId1."::H".$roleId2."::H".$roleId3."',2)", array());
        $adb->pquery("INSERT INTO vtiger_role VALUES('H".$roleId4."','Sales Manager','H".$roleId1."::H".$roleId2."::H".$roleId3."::H".$roleId4."',3)", array());
        $adb->pquery("INSERT INTO vtiger_role VALUES('H".$roleId5."','Sales Person','H".$roleId1."::H".$roleId2."::H".$roleId3."::H".$roleId4."::H".$roleId5."',4)", array());

		//INSERT INTO vtiger_role2profile
		$adb->pquery("INSERT INTO vtiger_role2profile VALUES ('H".$roleId2."',".$profileId1.")", array());
		$adb->pquery("INSERT INTO vtiger_role2profile VALUES ('H".$roleId3."',".$profileId2.")", array());
	  	$adb->pquery("INSERT INTO vtiger_role2profile VALUES ('H".$roleId4."',".$profileId2.")", array());
		$adb->pquery("INSERT INTO vtiger_role2profile VALUES ('H".$roleId5."',".$profileId2.")", array());

		//New Security Start
		//Inserting into vtiger_profile vtiger_table
		$adb->pquery("INSERT INTO vtiger_profile VALUES ('".$profileId1."','Administrator','Admin Profile')", array());
		$adb->pquery("INSERT INTO vtiger_profile VALUES ('".$profileId2."','Sales Profile','Profile Related to Sales')", array());
		$adb->pquery("INSERT INTO vtiger_profile VALUES ('".$profileId3."','Support Profile','Profile Related to Support')", array());
		$adb->pquery("INSERT INTO vtiger_profile VALUES ('".$profileId4."','Guest Profile','Guest Profile for Test Users')", array());

		//Inserting into vtiger_profile2gloabal permissions
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId1."',1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId1."',2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId2."',1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId2."',2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId3."',1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId3."',2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId4."',1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2globalpermissions VALUES ('".$profileId4."',2,1)", array());

		//Inserting into vtiger_profile2tab
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",6,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",7,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",13,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",14,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",15,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",16,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",18,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",19,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",20,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",21,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",22,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",23,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",24,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",25,0)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",26,0)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId1.",27,0)", array());

		//Inserting into vtiger_profile2tab
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",6,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",7,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",13,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",14,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",15,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",16,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",18,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",19,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",20,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",21,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",22,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",23,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",24,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",25,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",26,0)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId2.",27,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",6,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",7,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",13,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",14,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",15,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",16,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",18,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",19,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",20,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",21,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",22,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",23,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",24,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",25,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",26,0)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId3.",27,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",6,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",7,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",13,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",14,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",15,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",16,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",18,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",19,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",20,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",21,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",22,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",23,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",24,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",25,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",26,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2tab VALUES (".$profileId4.",27,0)", array());
		//Inserting into vtiger_profile2standardpermissions  Adminsitrator

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",2,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",2,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",2,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",2,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",2,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",4,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",4,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",4,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",4,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",4,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",6,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",6,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",6,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",6,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",6,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",7,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",7,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",7,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",7,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",7,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",8,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",8,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",8,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",8,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",8,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",9,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",9,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",9,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",9,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",9,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",13,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",13,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",13,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",13,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",13,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",14,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",14,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",14,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",14,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",14,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",15,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",15,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",15,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",15,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",15,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",16,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",16,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",16,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",16,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",16,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",18,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",18,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",18,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",18,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",18,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",19,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",19,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",19,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",19,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",19,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",20,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",20,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",20,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",20,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",20,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",21,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",21,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",21,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",21,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",21,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",22,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",22,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",22,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",22,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",22,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",23,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",23,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",23,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",23,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",23,4,0)", array());

        $adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",26,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",26,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",26,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",26,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId1.",26,4,0)", array());

		//INSERT INTO Profile 2 std permissions for Sales User
		//Help Desk Create/Delete not allowed. Read-Only
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",2,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",2,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",2,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",2,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",2,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",4,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",4,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",4,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",4,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",4,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",6,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",6,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",6,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",6,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",6,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",7,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",7,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",7,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",7,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",7,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",8,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",8,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",8,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",8,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",8,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",9,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",9,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",9,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",9,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",9,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",13,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",13,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",13,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",13,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",13,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",14,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",14,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",14,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",14,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",14,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",15,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",15,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",15,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",15,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",15,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",16,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",16,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",16,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",16,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",16,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",18,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",18,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",18,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",18,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",18,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",19,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",19,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",19,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",19,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",19,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",20,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",20,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",20,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",20,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",20,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",21,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",21,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",21,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",21,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",21,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",22,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",22,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",22,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",22,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",22,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",23,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",23,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",23,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",23,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",23,4,0)", array());


        	$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",26,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",26,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",26,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",26,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId2.",26,4,0)", array());

		//Inserting into vtiger_profile2std for Support Profile
		// Potential is read-only
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",2,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",2,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",2,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",2,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",2,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",4,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",4,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",4,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",4,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",4,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",6,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",6,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",6,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",6,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",6,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",7,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",7,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",7,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",7,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",7,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",8,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",8,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",8,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",8,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",8,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",9,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",9,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",9,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",9,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",9,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",13,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",13,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",13,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",13,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",13,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",14,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",14,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",14,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",14,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",14,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",15,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",15,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",15,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",15,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",15,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",16,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",16,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",16,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",16,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",16,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",18,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",18,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",18,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",18,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",18,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",19,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",19,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",19,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",19,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",19,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",20,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",20,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",20,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",20,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",20,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",21,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",21,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",21,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",21,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",21,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",22,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",22,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",22,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",22,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",22,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",23,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",23,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",23,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",23,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",23,4,0)", array());


        $adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",26,0,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",26,1,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",26,2,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",26,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId3.",26,4,0)", array());

		//Inserting into vtiger_profile2stdper for Profile Guest Profile
		//All Read-Only
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",2,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",2,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",2,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",2,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",2,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",4,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",4,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",4,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",4,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",4,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",6,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",6,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",6,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",6,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",6,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",7,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",7,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",7,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",7,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",7,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",8,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",8,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",8,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",8,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",8,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",9,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",9,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",9,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",9,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",9,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",13,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",13,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",13,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",13,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",13,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",14,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",14,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",14,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",14,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",14,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",15,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",15,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",15,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",15,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",15,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",16,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",16,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",16,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",16,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",16,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",18,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",18,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",18,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",18,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",18,4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",19,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",19,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",19,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",19,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",19,4,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",20,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",20,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",20,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",20,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",20,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",21,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",21,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",21,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",21,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",21,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",22,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",22,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",22,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",22,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",22,4,0)", array());

		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",23,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",23,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",23,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",23,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",23,4,0)", array());


        $adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",26,0,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",26,1,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",26,2,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",26,3,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2standardpermissions VALUES (".$profileId4.",26,4,0)", array());

		//Inserting into vtiger_profile 2 utility Admin
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",2,5,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",2,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",4,5,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",4,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",6,5,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",6,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",7,5,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",7,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",8,6,0)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",7,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",6,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",4,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",13,5,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",13,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",13,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",14,5,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",14,6,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",7,9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",18,5,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",18,6,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",7,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",6,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",4,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",2,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",13,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",14,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId1.",18,10,0)", array());
        

		//Inserting into vtiger_profile2utility Sales Profile
		//Import Export Not Allowed.
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",2,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",2,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",4,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",4,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",6,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",6,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",7,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",7,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",8,6,1)", array());
       	$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",7,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",6,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",4,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",13,5,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",13,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",13,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",14,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",14,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",7,9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",18,5,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",18,6,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",7,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",6,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",4,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",2,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",13,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",14,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId2.",18,10,0)", array());
       
		//Inserting into vtiger_profile2utility Support Profile
		//Import Export Not Allowed.
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",2,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",2,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",4,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",4,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",6,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",6,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",7,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",7,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",8,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",7,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",6,8,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",4,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",13,5,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",13,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",13,8,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",14,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",14,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",7,9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",18,5,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",18,6,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",7,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",6,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",4,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",2,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",13,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",14,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId3.",18,10,0)", array());
        

		//Inserting into vtiger_profile2utility Guest Profile Read-Only
		//Import Export BusinessCar Not Allowed.
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",2,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",2,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",4,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",4,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",6,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",6,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",7,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",7,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",8,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",7,8,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",6,8,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",4,8,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",13,5,1)", array());
    	$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",13,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",13,8,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",14,5,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",14,6,1)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",7,9,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",18,5,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",18,6,1)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",7,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",6,10,0)", array());
        $adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",4,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",2,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",13,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",14,10,0)", array());
		$adb->pquery("INSERT INTO vtiger_profile2utility VALUES (".$profileId4.",18,10,0)", array());
        

		 // Invalidate any cached information
    	VTCacheUtils::clearRoleSubordinates();

		//Inserting into vtiger_groups table
		$groupId1 = $adb->getUniqueID("vtiger_users");
		$groupId2 = $adb->getUniqueID("vtiger_users");
		$groupId3 = $adb->getUniqueID("vtiger_users");

		$adb->pquery("INSERT INTO vtiger_groups VALUES ('".$groupId1."','Team Selling','Group Related to Sales')", array());
		$adb->pquery("INSERT INTO vtiger_group2role VALUES ('".$groupId1."','H".$roleId4."')", array());
		$adb->pquery("INSERT INTO vtiger_group2rs VALUES ('".$groupId1."','H".$roleId5."')", array());

		$adb->pquery("INSERT INTO vtiger_groups VALUES ('".$groupId2."','Marketing Group','Group Related to Marketing Activities')", array());
		$adb->pquery("INSERT INTO vtiger_group2role VALUES ('".$groupId2."','H".$roleId2."')", array());
		$adb->pquery("INSERT INTO vtiger_group2rs VALUES ('".$groupId2."','H".$roleId3."')", array());

		$adb->pquery("INSERT INTO vtiger_groups VALUES ('".$groupId3."','Support Group','Group Related to providing Support to Customers')", array());
		$adb->pquery("INSERT INTO vtiger_group2role VALUES ('".$groupId3."','H".$roleId3."')", array());
		$adb->pquery("INSERT INTO vtiger_group2rs VALUES ('".$groupId3."','H".$roleId3."')", array());

		// Setting user group relation for admin user
	 	$adb->pquery("INSERT INTO vtiger_users2group VALUES (?,?)", array($groupId2, $adminUserId), array());

		//INSERT INTO vtiger_profile2field
		insertProfile2field($profileId1);
        insertProfile2field($profileId2);
        insertProfile2field($profileId3);
        insertProfile2field($profileId4);

		insert_def_org_field();
	}
	
	public static function createUser() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Get AdoDB instance and new Users object\n", FILE_APPEND);
		$adb = PearDatabase::getInstance();
		
		$adminPassword = $_SESSION['config_file_info']['password'];
		$userDateFormat = $_SESSION['config_file_info']['dateformat'];
		$userTimeZone = $_SESSION['config_file_info']['timezone'];
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $userFirstName = $_SESSION['config_file_info']['firstname']; 
        $userLastName = $_SESSION['config_file_info']['lastname']; 
        $userLanguage = $_SESSION['config_file_info']['default_language'];
        // create default admin user
    	$user = CRMEntity::getInstance('Users');
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $user->column_fields["first_name"] = $userFirstName; 
		$user->column_fields["last_name"] = $userLastName; 
        //Ends
        $user->column_fields["user_name"] = 'admin';
        $user->column_fields["status"] = 'Active';
        $user->column_fields["is_admin"] = 'on';
        $user->column_fields["user_password"] = $adminPassword;
        $user->column_fields["time_zone"] = $userTimeZone;
        $user->column_fields["language"] = $userLanguage;
        $user->column_fields["holidays"] = 'de,en_uk,fr,it,us,';
        $user->column_fields["workdays"] = '0,1,2,3,4,5,6,';
        $user->column_fields["weekstart"] = '1';
        $user->column_fields["namedays"] = '';
        $user->column_fields["currency_id"] = 1;
        $user->column_fields["reminder_interval"] = '1 Minute';
        $user->column_fields["reminder_next_time"] = date('Y-m-d H:i');
		$user->column_fields["date_format"] = $userDateFormat;
		$user->column_fields["hour_format"] = 'am/pm';
		$user->column_fields["start_hour"] = '08:00';
		$user->column_fields["end_hour"] = '23:00';
		$user->column_fields["imagename"] = '';
		$user->column_fields["internal_mailer"] = '1';
		$user->column_fields["activity_view"] = 'This Week';
		$user->column_fields["lead_view"] = 'Today';

		$adminEmail = (!empty($_SESSION['config_file_info']['admin_email'])) ? $_SESSION['config_file_info']['admin_email'] : 'admin@berlicrm.de';
		$user->column_fields["email1"] = $adminEmail;
		$user->column_fields["roleid"] = 'H2';
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Save User\n", FILE_APPEND);
        $user->save("Users");
        $adminUserId = 1;
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Update User ID\n", FILE_APPEND);
		//due to late user entry the groups already exist, so cheat admin to id 1
		$adb->pquery("UPDATE vtiger_users SET id = ? WHERE id = ?;", array($adminUserId, $user->id));
		
		//Creating the flat files for admin user
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Privilege file\n", FILE_APPEND);
		createUserPrivilegesfile($adminUserId);
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Sharing file\n", FILE_APPEND);
		createUserSharingPrivilegesfile($adminUserId);
		return true;
	}

	/**
	 * Function add necessary schema for event handlers and workflows, also add defaul workflows
	 */
	public static function installDefaultEventsAndWorkflows() {
		global $adb;
		$adb = PearDatabase::getInstance();

		// Register All the Events
		self::registerEvents($adb);

		// Register All the Entity Methods
		self::registerEntityMethods($adb);

		// Populate Default Workflows
		self::populateDefaultWorkflows($adb);

		// Populate Links
		self::populateLinks();

		// Set Help Information for Fields
		self::setFieldHelpInfo();

		// Register Cron Jobs
		self::registerCronTasks();
	}

	/**
	 *  Register all the Cron Tasks
	 */
	public static function registerCronTasks() {
		vimport('~~vtlib/Vtiger/Cron.php');
		Vtiger_Cron::register( 'Workflow', 'cron/modules/com_vtiger_workflow/com_vtiger_workflow.service', 900, 'com_vtiger_workflow', 1, 1, 'LBL_WORKFLOW_DES');
		Vtiger_Cron::register( 'RecurringInvoice', 'cron/modules/SalesOrder/RecurringInvoice.service', 86400, 'SalesOrder', 1, 2, 'LBL_REC_INVOICE_DES');
		Vtiger_Cron::register( 'SendReminder', 'cron/SendReminder.service', 900, 'Calendar', 1, 3, 'LBL_SENDREMINDER_DES');
		Vtiger_Cron::register( 'ScheduleReports', 'cron/modules/Reports/ScheduleReports.service', 900, 'Reports', 1, 4, 'LBL_REPORT_DES');
		Vtiger_Cron::register( 'MailScanner', 'cron/MailScanner.service', 900, 'Settings', 1, 5, 'LBL_MAILSCANNER_DES');
	}

	/**
	 * Function registers all the event handlers
	 */
	static function registerEvents($adb) {
		vimport('~~include/events/include.inc');
		$em = new VTEventsManager($adb);

		// Registering event for Recurring Invoices
		$em->registerHandler('vtiger.entity.aftersave', 'modules/SalesOrder/RecurringInvoiceHandler.php', 'RecurringInvoiceHandler');

		//Registering Entity Delta handler for before save and after save events of the record to track the field value changes
		$em->registerHandler('vtiger.entity.beforesave', 'data/VTEntityDelta.php', 'VTEntityDelta');
		$em->registerHandler('vtiger.entity.aftersave', 'data/VTEntityDelta.php', 'VTEntityDelta');

		// Workflow manager
		$dependentEventHandlers = array('VTEntityDelta');
		$dependentEventHandlersJson = Zend_Json::encode($dependentEventHandlers);
		$em->registerHandler('vtiger.entity.aftersave', 'modules/com_vtiger_workflow/VTEventHandler.inc', 'VTWorkflowEventHandler',
									'',$dependentEventHandlersJson);

		//Registering events for On modify
		$em->registerHandler('vtiger.entity.afterrestore', 'modules/com_vtiger_workflow/VTEventHandler.inc', 'VTWorkflowEventHandler');

		// Registering event for HelpDesk - To reset from_portal value
		$em->registerHandler('vtiger.entity.aftersave.final', 'modules/HelpDesk/HelpDeskHandler.php', 'HelpDeskHandler');
	}

	/**
	 * Function registers all the work flow custom entity methods
	 * @param <PearDatabase> $adb
	 */
	static function registerEntityMethods($adb) {
		vimport("~~modules/com_vtiger_workflow/include.inc");
		vimport("~~modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc");
		vimport("~~modules/com_vtiger_workflow/VTEntityMethodManager.inc");
		$emm = new VTEntityMethodManager($adb);

		// Registering method for Updating Inventory Stock
		$emm->addEntityMethod("SalesOrder","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");//Adding EntityMethod for Updating Products data after creating SalesOrder
		$emm->addEntityMethod("Invoice","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");//Adding EntityMethod for Updating Products data after creating Invoice

		// Register Entity Method for Customer Portal Login details email notification task
		$emm->addEntityMethod("Contacts","SendPortalLoginDetails","modules/Contacts/ContactsHandler.php","Contacts_sendCustomerPortalLoginDetails");

		// Register Entity Method for Email notification on ticket creation from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOnPortalTicketCreation","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_nofifyOnPortalTicketCreation");

		// Register Entity Method for Email notification on ticket comment from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOnPortalTicketComment","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyOnPortalTicketComment");

		// Register Entity Method for Email notification to Record Owner on ticket change, which is not from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOwnerOnTicketChange","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyOwnerOnTicketChange");

		// Register Entity Method for Email notification to Related Customer on ticket change, which is not from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyParentOnTicketChange","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyParentOnTicketChange");
	}

	/**
	 * Function adds default system workflows
	 * @param <PearDatabase> $adb
	 */
	static function populateDefaultWorkflows($adb) {
		vimport("~~modules/com_vtiger_workflow/include.inc");
		vimport("~~modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc");
		vimport("~~modules/com_vtiger_workflow/VTEntityMethodManager.inc");
		vimport("~~modules/com_vtiger_workflow/VTTaskManager.inc");

		// register the workflow tasks
		$taskTypes = array();
		$defaultModules = array('include' => array(), 'exclude'=>array());
		$createToDoModules = array('include' => array("Leads","Accounts","Potentials","Contacts","HelpDesk","Campaigns","Quotes","PurchaseOrder","SalesOrder","Invoice"), 'exclude'=>array("Calendar", "FAQ", "Events"));
		$createEventModules = array('include' => array("Leads","Accounts","Potentials","Contacts","HelpDesk","Campaigns"), 'exclude'=>array("Calendar", "FAQ", "Events"));

		$taskTypes[] = array("name"=>"VTEmailTask", "label"=>"Send Mail", "classname"=>"VTEmailTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTEmailTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTEmailTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTEntityMethodTask", "label"=>"Invoke Custom Function", "classname"=>"VTEntityMethodTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTEntityMethodTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateTodoTask", "label"=>"Create Todo", "classname"=>"VTCreateTodoTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateTodoTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateTodoTask.tpl", "modules"=>$createToDoModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateEventTask", "label"=>"Create Event", "classname"=>"VTCreateEventTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateEventTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateEventTask.tpl", "modules"=>$createEventModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTUpdateFieldsTask", "label"=>"Update Fields", "classname"=>"VTUpdateFieldsTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTUpdateFieldsTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTUpdateFieldsTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateEntityTask", "label"=>"Create Entity", "classname"=>"VTCreateEntityTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateEntityTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateEntityTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTSMSTask", "label"=>"SMS Task", "classname"=>"VTSMSTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTSMSTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTSMSTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'SMSNotifier');

		foreach ($taskTypes as $taskType) {
			VTTaskType::registerTaskType($taskType);
		}

		// Creating Workflow for Updating Inventory Stock for Invoice
		$vtWorkFlow = new VTWorkflowManager($adb);
		$invWorkFlow = $vtWorkFlow->newWorkFlow("Invoice");
		$invWorkFlow->test = '[{"fieldname":"subject","operation":"does not contain","value":"`!`"}]';
		$invWorkFlow->description = "LBL_INVENTORY_UPDATE";
		$invWorkFlow->defaultworkflow = 1;
		$vtWorkFlow->save($invWorkFlow);

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEntityMethodTask', $invWorkFlow->id);
		$task->active=true;
		$task->methodName = "UpdateInventory";
		$tm->saveTask($task);

		// Creating Workflow for Accounts when Notifyowner is true
		$vtaWorkFlow = new VTWorkflowManager($adb);
		$accWorkFlow = $vtaWorkFlow->newWorkFlow("Accounts");
		$accWorkFlow->test = '[{"fieldname":"notify_owner","operation":"is","value":"true:boolean"}]';
		$accWorkFlow->description = "LBL_SEND_OWNER_EMAIL";
		$accWorkFlow->executionCondition=2;
		$accWorkFlow->defaultworkflow = 1;
		$vtaWorkFlow->save($accWorkFlow);
		$id1=$accWorkFlow->id;

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$accWorkFlow->id);
		$task->active=true;
		$task->methodName = "NotifyOwner";
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Account Creation";
		$task->content = "An Account has been assigned to you on the CRM<br>Details of account are:<br><br>".
				"Account Id: ".'<b>$account_no</b><br>'."Account Name: ".'<b>$accountname</b><br>'."Rating: ".'<b>$rating</b><br>'.
				"Industry: ".'<b>$industry</b><br>'."Account Type: ".'<b>$accounttype</b><br>'.
				"Description:".'<b>$description</b><br><br><br>'."Thank You";
		$task->summary="An account has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));

		// Creating Workflow for Contacts when Notifyowner is true

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$conWorkFlow = 	$vtcWorkFlow->newWorkFlow("Contacts");
		$conWorkFlow->summary="A contact has been created ";
		$conWorkFlow->executionCondition=2;
		$conWorkFlow->test = '[{"fieldname":"notify_owner","operation":"is","value":"true:boolean"}]';
		$conWorkFlow->description = "LBL_SEND_OWNER_EMAIL";
		$conWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($conWorkFlow);
		$id1=$conWorkFlow->id;
		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$conWorkFlow->id);
		$task->active=true;
		$task->methodName = "NotifyOwner";
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Contact Creation";
		$task->content = "A Contact has been assigned to you on the CRM<br>Details of Contact are :<br><br>".
				"Contact Id:".'<b>$contact_no</b><br>'."LastName:".'<b>$lastname</b><br>'."FirstName:".'<b>$firstname</b><br>'.
				"Lead Source:".'<b>$leadsource</b><br>'.
				"Department:".'<b>$department</b><br>'.
				"Description:".'<b>$description</b><br><br><br>'."Thank You<br>Admin";
		$task->summary="A contact has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));


		// Creating Workflow for Contacts when PortalUser is true

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$conpuWorkFlow = $vtcWorkFlow->newWorkFlow("Contacts");
		$conpuWorkFlow->test = '[{"fieldname":"portal","operation":"is","value":"true:boolean"}]';
		$conpuWorkFlow->description = "LBL_SEND_PORTAL_EMAIL";
		$conpuWorkFlow->executionCondition=2;
		$conpuWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($conpuWorkFlow);
		$id1=$conpuWorkFlow->id;

                $taskManager = new VTTaskManager($adb);
                $task = $taskManager->createTask('VTEntityMethodTask', $id1);
		$task->active = true;
		$task->summary = 'Email Customer Portal Login Details';
		$task->methodName = "SendPortalLoginDetails";
		$taskManager->saveTask($task);
		// Creating Workflow for Potentials

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$potentialWorkFlow = $vtcWorkFlow->newWorkFlow("Potentials");
		$potentialWorkFlow->description = "LBL_SEND_POTENTIAL_EMAIL";
		$potentialWorkFlow->executionCondition=1;
		$potentialWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($potentialWorkFlow);
		$id1=$potentialWorkFlow->id;

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$potentialWorkFlow->id);

		$task->active=true;
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Potential Assignment";
		$task->content = "An Potential has been assigned to you on the CRM<br>Details of Potential are :<br><br>".
				"Potential No:".'<b>$potential_no</b><br>'."Potential Name:".'<b>$potentialname</b><br>'.
				"Amount:".'<b>$amount</b><br>'.
				"Expected Close Date:".'<b>$closingdate</b><br>'.
				"Type:".'<b>$opportunity_type</b><br><br><br>'.
				"Description :".'$description<br>'."<br>Thank You<br>Admin";

		$task->summary="A Potential has been created ";
		$tm->saveTask($task);

		$workflowManager = new VTWorkflowManager($adb);
		$taskManager = new VTTaskManager($adb);

		// Contact workflow on creation/modification
		$contactWorkFlow = $workflowManager->newWorkFlow("Contacts");
		$contactWorkFlow->test = '';
		$contactWorkFlow->description = "LBL_CONT_CRE_OR_MOD";
		$contactWorkFlow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$contactWorkFlow->defaultworkflow = 1;
		$workflowManager->save($contactWorkFlow);

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$contactWorkFlow->id);

		$task->active=true;
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Contact Assignment";
		$task->content = "A Contact has been assigned to you on the CRM<br>The Details of the Contact are:<br><br>".
				"Contact Id:".'<b>$contact_no</b><br>'."LastName:".'<b>$lastname</b><br>'."FirstName:".'<b>$firstname</b><br>'.
				"Lead Source:".'<b>$leadsource</b><br>'.
				"Department:".'<b>$department</b><br>'.
				"<br>Thank You<br>";

		$task->summary="A contact has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));
                
		// Trouble Tickets workflow on creation from Customer Portal
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
		$helpDeskWorkflow->description = "LBL_PORTAL_TICKET_CR_EMAIL";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_FIRST_SAVE;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner and the Related Contact when Ticket is created from Portal';
		$task->methodName = "NotifyOnPortalTicketCreation";
		$taskManager->saveTask($task);

		// Trouble Tickets workflow on ticket update from Customer Portal
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
		$helpDeskWorkflow->description = "LBL_PORTAL_TICKET_UP_EMAIL";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_MODIFY;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner when Comment is added to a Ticket from Customer Portal';
		$task->methodName = "NotifyOnPortalTicketComment";
		$taskManager->saveTask($task);

		// Trouble Tickets workflow on ticket change, which is not from Customer Portal - Both Record Owner and Related Customer
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"false:boolean"}]';
		$helpDeskWorkflow->description = "Workflow for Ticket Change, not from the Portal";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner on Ticket Change, which is not done from Portal';
		$task->methodName = "NotifyOwnerOnTicketChange";
		$taskManager->saveTask($task);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Related Customer on Ticket Change, which is not done from Portal';
		$task->methodName = "NotifyParentOnTicketChange";
		$taskManager->saveTask($task);

		// Events workflow when Send Notification is checked
		$eventsWorkflow = $workflowManager->newWorkFlow("Events");
		$eventsWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
		$eventsWorkflow->description = "LBL_EVENT_NOTIFY_EMAIL";
		$eventsWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$eventsWorkflow->defaultworkflow = 1;
		$workflowManager->save($eventsWorkflow);

		$task = $taskManager->createTask('VTEmailTask', $eventsWorkflow->id);
		$task->active = true;
		$task->summary = 'Send Notification Email to Record Owner';
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Event :  \$subject";
		$task->content = '$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name) ,<br/>'
						.'<b>Activity Notification Details:</b><br/>'
						.'Subject             : $subject<br/>'
						.'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'End date and time   : $due_date  $time_end ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'Status              : $eventstatus <br/>'
						.'Priority            : $taskpriority <br/>'
						.'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
												.'$(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
						.'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
						.'Location            : $location <br/>'
						.'Description         : $description';
		$taskManager->saveTask($task);

		// Calendar workflow when Send Notification is checked
		$calendarWorkflow = $workflowManager->newWorkFlow("Calendar");
		$calendarWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
		$calendarWorkflow->description = "LBL_TASK_NOTIFY_EMAIL";
		$calendarWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$calendarWorkflow->defaultworkflow = 1;
		$workflowManager->save($calendarWorkflow);

		$task = $taskManager->createTask('VTEmailTask', $calendarWorkflow->id);
		$task->active = true;
		$task->summary = 'Send Notification Email to Record Owner';
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Task :  \$subject";
		$task->content = '$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name) ,<br/>'
						.'<b>Task Notification Details:</b><br/>'
						.'Subject : $subject<br/>'
						.'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'End date and time   : $due_date ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'Status              : $taskstatus <br/>'
						.'Priority            : $taskpriority <br/>'
						.'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
						.'$(parent_id         : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
						.'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
						.'Location            : $location <br/>'
						.'Description         : $description';
		$taskManager->saveTask($task);
	}

	/**
	 * Function adds default details view links
	 */
	public static function populateLinks() {
		vimport('~~vtlib/Vtiger/Module.php');

		// Links for Accounts module
		$accountInstance = Vtiger_Module::getInstance('Accounts');
		// Detail View Custom link
		$accountInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$accountInstance->addLink('DETAILVIEWBASIC', 'LBL_SHOW_ACCOUNT_HIERARCHY', 'index.php?module=Accounts&action=AccountHierarchy&accountid=$RECORD$');

		$leadInstance = Vtiger_Module::getInstance('Leads');
		$leadInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$leadInstance->addLink(
			'DETAILVIEWBASIC', 'Export vCard',
			'index.php?module=Leads&action=getvCard&src_module=Leads&src_record=$RECORD$',
			''
		);

		$contactInstance = Vtiger_Module::getInstance('Contacts');
		$contactInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$contactInstance->addLink(
			'DETAILVIEWBASIC', 'Export vCard',
			'index.php?module=Contacts&action=getvCard&src_module=Contacts&src_record=$RECORD$',
			''
		);
	}

	/**
	 * Function add help information on special fields
	 */
	public static function setFieldHelpInfo() {
		// Added Help Info for Hours and Days fields of HelpDesk module.
		vimport('~~vtlib/Vtiger/Module.php');
		$helpDeskModule = Vtiger_Module::getInstance('HelpDesk');
		$field1 = Vtiger_Field::getInstance('hours',$helpDeskModule);
		$field2 = Vtiger_Field::getInstance('days',$helpDeskModule);

		$field1->setHelpInfo('This gives the estimated hours for the Ticket.'.
					'<br>When the same ticket is added to a Service Contract,'.
					'based on the Tracking Unit of the Service Contract,'.
					'Used units is updated whenever a ticket is Closed.');

		$field2->setHelpInfo('This gives the estimated days for the Ticket.'.
					'<br>When the same ticket is added to a Service Contract,'.
					'based on the Tracking Unit of the Service Contract,'.
					'Used units is updated whenever a ticket is Closed.');

		$usersModuleInstance = Vtiger_Module::getInstance('Users');
		$field1 = Vtiger_Field::getInstance('currency_grouping_pattern', $usersModuleInstance);
		$field2 = Vtiger_Field::getInstance('currency_decimal_separator', $usersModuleInstance);
		$field3 = Vtiger_Field::getInstance('currency_grouping_separator', $usersModuleInstance);
		$field4 = Vtiger_Field::getInstance('currency_symbol_placement', $usersModuleInstance);

		$field1->setHelpInfo("<b>Currency - Digit Grouping Pattern</b> <br/><br/>".
									"This pattern specifies the format in which the currency separator will be placed.");
		$field2->setHelpInfo("<b>Currency - Decimal Separator</b> <br/><br/>".
											"Decimal separator specifies the separator to be used to separate ".
											"the fractional values from the whole number part. <br/>".
											"<b>Eg:</b> <br/>".
											". => 123.45 <br/>".
											", => 123,45 <br/>".
											"' => 123'45 <br/>".
											"  => 123 45 <br/>".
											"$ => 123$45 <br/>");
		$field3->setHelpInfo("<b>Currency - Grouping Separator</b> <br/><br/>".
											"Grouping separator specifies the separator to be used to group ".
											"the whole number part into hundreds, thousands etc. <br/>".
											"<b>Eg:</b> <br/>".
											". => 123.456.789 <br/>".
											", => 123,456,789 <br/>".
											"' => 123'456'789 <br/>".
											"  => 123 456 789 <br/>".
											"$ => 123$456$789 <br/>");
		$field4->setHelpInfo("<b>Currency - Symbol Placement</b> <br/><br/>".
											"Symbol Placement allows you to configure the position of the ".
											"currency symbol with respect to the currency value.<br/>".
											"<b>Eg:</b> <br/>".
											"$1.0 => $123,456,789.50 <br/>".
											"1.0$ => 123,456,789.50$ <br/>");
	}
	
	//crm-now: modifications to DB during install
	public static function setCRMNOWmodifications() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Include Webservice Utils, get AdoDB instance\n", FILE_APPEND);
		vimport('~~include/Webservices/Utils.php');
		$adb = PearDatabase::getInstance();
		
		//crm-now: new settings menu for PDF templates
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Update vtiger_field to add PDF Templates entry\n", FILE_APPEND);
		$ID = $adb->getUniqueID('vtiger_settings_field');
		$params = array($ID, '3', 'LBL_PDF_TEMPLATES', '', 'LBL_PDF_TEMPLATE_DESCRIPTION', 'index.php?parent=Settings&module=Vtiger&view=listpdftexttemplates', '3', '0', '0');
		$adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid` ,`blockid` ,`name` ,`iconpath` ,`description` ,`linkto` ,`sequence` ,`active`, `pinned`)
		VALUES (".generateQuestionMarks($params).")", $params);
		// $adb->query("UPDATE `vtiger_settings_field_seq` SET `id` = '".$ID."' ");
		// $adb->query("CREATE TABLE IF NOT EXISTS `crmnow_multistarttext` (
		  // `starttextid` int(11) NOT NULL AUTO_INCREMENT,
		  // `starttexttitle` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  // `multistext` text COLLATE utf8_unicode_ci NOT NULL,
		  // `texttypes` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  // PRIMARY KEY (`starttextid`)
		// ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");
		// $adb->query("CREATE TABLE IF NOT EXISTS `crmnow_multiendtext` (
		  // `endtextid` int(11) NOT NULL AUTO_INCREMENT,
		  // `endtexttitle` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  // `multietext` text COLLATE utf8_unicode_ci NOT NULL,
		  // `texttype` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  // PRIMARY KEY (`endtextid`)
		// ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");
		//crm-now: add index for performance increase
		// $adb->query("ALTER TABLE `vtiger_role2picklist` ADD INDEX(`picklistvalueid`)");
		
		//added for German salutation
		// $adb->query("UPDATE `vtiger_salutationtype` SET `salutationtype` = 'Sehr geehrter Herr' WHERE `vtiger_salutationtype`.`salutationid` =2");
		// $adb->query("UPDATE `vtiger_salutationtype` SET `salutationtype` = 'Sehr geehrte Frau' WHERE `vtiger_salutationtype`.`salutationid` =3");
		// $adb->query("UPDATE `vtiger_salutationtype` SET `salutationtype` = 'Sehr geehrter Herr Dr.' WHERE `vtiger_salutationtype`.`salutationid` =4");
		// $adb->query("UPDATE `vtiger_salutationtype` SET `salutationtype` = 'Sehr geehrte Frau Dr.' WHERE `vtiger_salutationtype`.`salutationid` =5");
		// $adb->query("UPDATE `vtiger_salutationtype` SET `salutationtype` = 'Sehr geehrter Herr Prof.' WHERE `vtiger_salutationtype`.`salutationid` =6");
		
		// delete all custom filters, except all
		// $adb->query("DELETE FROM `vtiger_customview` WHERE `vtiger_customview`.`viewname` != 'ALL' ");

		//Manufacturer -> new entries
		// $adb->query("UPDATE `vtiger_manufacturer` SET `manufacturer` = 'Muster AG' WHERE `vtiger_manufacturer`.`manufacturerid` =2 ");
		// $adb->query("UPDATE `vtiger_manufacturer` SET `manufacturer` = 'Sample Inc.' WHERE `vtiger_manufacturer`.`manufacturerid` =3 ");
		// $adb->query("UPDATE `vtiger_manufacturer` SET `manufacturer` = 'Beispiel GmbH' WHERE `vtiger_manufacturer`.`manufacturerid` =4 ");
		
		//Bank Information for Company Details Menue and PDF Output
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `management` varchar( 100 ) ");
		// //IRS name(Finanzamt)
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `irsname` varchar( 100 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `tax_id` varchar( 100 ) ");
		// //bank information
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankname` varchar( 100 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankstreet` varchar( 100 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankcity` varchar( 100 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankzip` varchar( 10 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankcountry` varchar( 100 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankaccount` varchar( 50 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankrouting` varchar( 50 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankswift` varchar( 50 ) ");
		// $adb->query(" ALTER TABLE vtiger_organizationdetails ADD `bankiban` varchar( 50 ) ");
		
        // dynamic blocks
        // $adb->query("CREATE TABLE IF NOT EXISTS `berli_dynamic_blocks` (
            // `moduleid` int(19) NOT NULL,
            // `picklistid` int(19) NOT NULL,
            // `picklistvalueid` int(19) NOT NULL,
            // `blockid` int(19) NOT NULL,
            // `initialstatus` int(1) NOT NULL,
            // `blocked` int(1) NOT NULL,
            // PRIMARY KEY (`moduleid`,`picklistid`,`picklistvalueid`,`blockid`),
            // KEY `picklistid` (`picklistid`)
            // ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        // $adb->query("ALTER TABLE `berli_dynamic_blocks`
            // ADD CONSTRAINT `berli_dynamic_blocks_ibfk_1` FOREIGN KEY (`picklistid`) REFERENCES `vtiger_picklist` (`picklistid`) ON DELETE CASCADE");
		
		// add web service: retrievedocattachment
		// $operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
		// vtws_addWebserviceOperationParam($operationId,'id','string','1');
		// vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');
		
		//crm-now: add all modules to tracking (this was done in migration script)
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Update Modtracker tracked modules\n", FILE_APPEND);
		if(file_exists('modules/ModTracker/ModTrackerUtils.php')) {
			require_once 'modules/ModTracker/ModTrackerUtils.php';
			$modules = $adb->pquery('SELECT * FROM vtiger_tab WHERE isentitytype = ?;', array(1));
			$rows = $adb->num_rows($modules);
			for($i=0; $i<$rows; $i++) {
				$tabid=$adb->query_result($modules, $i, 'tabid');
				$module=$adb->query_result($modules, $i, 'name');
				file_put_contents($path, "[".date('Y-m-d h:i:s')."] Track $module\n", FILE_APPEND);
				ModTrackerUtils::modTrac_changeModuleVisibility($tabid, 'module_enable');
			}
		}
		
		//last step, set info this system was installed
		$path = Install_Utils_Model::INSTALL_FINISHED;
		$fh = fopen($path, 'a+');
		fclose($fh);
		
		return true;
	}

}
