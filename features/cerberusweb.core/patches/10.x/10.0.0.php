<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `custom_field.uri`

list($columns,) = $db->metaTable('custom_field');

if(!array_key_exists('uri', $columns)) {
	$sql = "ALTER TABLE custom_field ADD COLUMN uri VARCHAR(128) NOT NULL DEFAULT '', ADD INDEX (uri)";
	$db->ExecuteMaster($sql);
	
	// Generate aliases for existing custom fields
	
	$fields = $db->GetArrayMaster("select id, name, (select name from custom_fieldset where id = custom_field.custom_fieldset_id) as custom_fieldset from custom_field");
	
	foreach($fields as $field) {
		$field_key = sprintf("%s%s",
			$field['custom_fieldset'] ? (DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['custom_fieldset'], MB_CASE_TITLE))) . '_') : '',
			DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['name'], MB_CASE_TITLE)))
		);
		
		$db->ExecuteMaster(sprintf("UPDATE custom_field SET uri = %s WHERE id = %d",
			$db->qstr($field_key),
			$field['id']
		));
	}
}

// ===========================================================================
// Add `identity`

if(!isset($tables['identity'])) {
	$sql = sprintf("
		CREATE TABLE `identity` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		pool_id int unsigned NOT NULL DEFAULT 0,
		name varchar(255) NOT NULL DEFAULT '',
		given_name varchar(255) NOT NULL DEFAULT '',
		family_name varchar(255) NOT NULL DEFAULT '',
		middle_name varchar(255) NOT NULL DEFAULT '',
		nickname varchar(255) NOT NULL DEFAULT '',
		username varchar(255) NOT NULL DEFAULT '',
		website varchar(255) NOT NULL DEFAULT '',
		email_id int unsigned NOT NULL DEFAULT 0,
		email_verified tinyint unsigned NOT NULL DEFAULT 0,
		gender varchar(1) NOT NULL DEFAULT '',
		birthdate varchar(10) not null DEFAULT '',
		zoneinfo varchar(255) NOT NULL DEFAULT '',
		locale varchar(32) NOT NULL DEFAULT '',
		phone_number varchar(32) NOT NULL DEFAULT '',
		phone_number_verified tinyint unsigned NOT NULL DEFAULT 0,
		address varchar(255) NOT NULL DEFAULT '',
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		KEY `pool_id` (`email_id`),
		KEY `email_id` (`email_id`),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['identity'] = 'identity';
	
	$package_json = <<< EOD
{
	"package": {
		"name": "Package Name",
		"revision": 1,
		"requires": {
			"cerb_version": "10.0.0",
			"plugins": []
		},
		"library": {},
		"configure": {
			"placeholders": [],
			"prompts": []
		}
	},
	"records": [
		{
			"uid": "tab_overview",
			"_context": "cerberusweb.contexts.profile.tab",
			"name": "Overview",
			"context": "cerb.contexts.identity",
			"extension_id": "cerb.profile.tab.dashboard",
			"extension_params": {
				"layout": "sidebar_left"
			}
		},
		{
			"uid": "profile_widget_fields",
			"_context": "cerberusweb.contexts.profile.widget",
			"name": "Identity",
			"extension_id": "cerb.profile.tab.widget.fields",
			"profile_tab_id": "{{{uid.tab_overview}}}",
			"pos": 1,
			"width_units": 4,
			"zone": "sidebar",
			"extension_params": {
				"context": "cerb.contexts.identity",
				"context_id": "{{record_id}}",
				"properties": [
					[
						"pool_id",
						"email_id",
						"email_verified",
						"family_name",
						"gender",
						"locale",
						"phone",
						"phone_verified",
						"zoneinfo",
						"username",
						"website",
						"created_at",
						"updated_at"
					]
				]
			}
		},
		{
			"uid": "profile_widget_discussion",
			"_context": "cerberusweb.contexts.profile.widget",
			"name": "Discussion",
			"extension_id": "cerb.profile.tab.widget.comments",
			"pos": 1,
			"profile_tab_id": "{{{uid.tab_overview}}}",
			"width_units": 4,
			"zone": "content",
			"extension_params": {
				"context": "cerb.contexts.identity",
				"context_id": "{{record_id}}",
				"height": ""
			}
		}
	]
}
EOD;
	
	$records_created = [];
	
	try {
		CerberusApplication::packages()->import($package_json, [], $records_created);
		
		// Insert profile tab ID in devblocks_settings
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerb.contexts.identity',%s)",
			$db->qstr(sprintf('[%d]', $records_created[CerberusContexts::CONTEXT_PROFILE_TAB]['tab_overview']['id']))
		));
		
	} catch(Exception_DevblocksValidationError $e) {
		//return FALSE;
	}
}

// ===========================================================================
// Add `identity_pool`

if(!isset($tables['identity_pool'])) {
	$sql = sprintf("
		CREATE TABLE `identity_pool` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['identity_pool'] = 'identity_pool';
	
	$package_json = <<< EOD
{
	"package": {
		"name": "Package Name",
		"revision": 1,
		"requires": {
			"cerb_version": "10.0.0",
			"plugins": []
		},
		"library": {},
		"configure": {
			"placeholders": [],
			"prompts": []
		}
	},
	"records": [
		{
			"uid": "tab_overview",
			"_context": "cerberusweb.contexts.profile.tab",
			"name": "Overview",
			"context": "cerb.contexts.identity.pool",
			"extension_id": "cerb.profile.tab.dashboard",
			"extension_params": {
				"layout": "sidebar_left"
			}
		},
		{
			"uid": "profile_widget_fields",
			"_context": "cerberusweb.contexts.profile.widget",
			"name": "Identity Pool",
			"extension_id": "cerb.profile.tab.widget.fields",
			"profile_tab_id": "{{{uid.tab_overview}}}",
			"pos": 1,
			"width_units": 4,
			"zone": "sidebar",
			"extension_params": {
				"context": "cerb.contexts.identity.pool",
				"context_id": "{{record_id}}",
				"properties": [
					[
						"id",
						"created_at"
						"updated_at"
					]
				]
			}
		},
		{
			"uid": "profile_widget_discussion",
			"_context": "cerberusweb.contexts.profile.widget",
			"name": "Discussion",
			"extension_id": "cerb.profile.tab.widget.comments",
			"pos": 1,
			"profile_tab_id": "{{{uid.tab_overview}}}",
			"width_units": 4,
			"zone": "content",
			"extension_params": {
				"context": "cerb.contexts.identity.pool",
				"context_id": "{{record_id}}",
				"height": ""
			}
		}
	]
}
EOD;
	
	$records_created = [];
	
	try {
		CerberusApplication::packages()->import($package_json, [], $records_created);
		
		// Insert profile tab ID in devblocks_settings
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerb.contexts.identity.pool',%s)",
			$db->qstr(sprintf('[%d]', $records_created[CerberusContexts::CONTEXT_PROFILE_TAB]['tab_overview']['id']))
		));
		
	} catch(Exception_DevblocksValidationError $e) {
		//return FALSE;
	}
}

// ===========================================================================
// Add portal pages and widgets

if(!isset($tables['portal_page'])) {
	$sql = sprintf("
		CREATE TABLE `portal_page` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		uri varchar(255) NOT NULL DEFAULT '',
		portal_id int(10) unsigned NOT NULL DEFAULT 0,
		is_private tinyint unsigned NOT NULL DEFAULT 0,
		pos tinyint unsigned NOT NULL DEFAULT 0,
		extension_id varchar(255) NOT NULL DEFAULT '',
		params_json text,
		updated_at int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		KEY `portal_id` (`portal_id`),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['portal_page'] = 'portal_page';
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
		$db->qstr('cerberusweb.core'),
		$db->qstr('card:search:cerb.contexts.portal.page'),
		$db->qstr('[{"context":"cerb.contexts.portal.tab","label_singular":"Tab","label_plural":"Tabs","query":"page.id:{{id}}"}]')
	));
}

if(!isset($tables['portal_widget'])) {
	$sql = sprintf("
		CREATE TABLE `portal_widget` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		extension_id varchar(255) NOT NULL DEFAULT '',
		portal_id int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		params_json text,
		width_units tinyint(3) unsigned NOT NULL DEFAULT '1',
		zone varchar(255) NOT NULL DEFAULT '',
		pos tinyint(255) DEFAULT '0',
		PRIMARY KEY (id),
		KEY `portal_id` (`portal_id`),
		KEY `extension_id` (`extension_id`),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['portal_widget'] = 'portal_widget';
}

// ===========================================================================
// Add `automation` table

if(!isset($tables['automation'])) {
	$sql = sprintf("
		CREATE TABLE `automation` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`extension_id` varchar(255) NOT NULL DEFAULT '',
		`extension_params_json` mediumtext,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`script` mediumtext,
		`policy_kata` text,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (extension_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation'] = 'automation';
}

// ===========================================================================
// Add `automation_datastore` table

if(!isset($tables['automation_datastore'])) {
	$sql = sprintf("
		CREATE TABLE `automation_datastore` (
		`data_key` varchar(255) NOT NULL DEFAULT '',
		`data_value` mediumtext,
		`expires_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (`data_key`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['automation_datastore'] = 'automation_datastore';
}

// ===========================================================================
// Add `automation_execution` table

if(!isset($tables['automation_execution'])) {
	$sql = sprintf("
	CREATE TABLE `automation_execution` (
		token varchar(64) NOT NULL DEFAULT '',
		uri varchar(255) NOT NULL DEFAULT '',
		state varchar(8) NOT NULL DEFAULT '',
		state_data mediumtext,
		expires_at int(10) unsigned NOT NULL DEFAULT 0,
		updated_at int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (token),
		INDEX (uri),
		INDEX (state),
		INDEX (expires_at),
		INDEX (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_execution'] = 'automation_execution';
}

// ===========================================================================
// Drop `email_signature.is_default`

list($columns,) = $db->metaTable('email_signature');

if(array_key_exists('is_default', $columns)) {
	$sql = "ALTER TABLE email_signature DROP COLUMN is_default";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return TRUE;
