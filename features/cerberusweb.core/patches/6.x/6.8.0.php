<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add custom_placeholders to `snippet`

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['custom_placeholders_json'])) {
	$db->Execute("ALTER TABLE snippet ADD COLUMN custom_placeholders_json MEDIUMTEXT");
	
	// Migrate old style snippet placeholders to JSON (single, w/default, multiple)
	$rs = $db->Execute("SELECT id, content FROM snippet");
	
	while($row = mysqli_fetch_assoc($rs)) {
		$content = $row['content'];
		
		// Extract unique placeholders and formalize
		
		// Multiple line
		$matches = array();
		if(preg_match_all("#\(\(\_\_(.*?)\_\_\)\)#", $content, $matches)) {
			$changes = array();
			$placeholders = array();
			
			foreach($matches[1] as $match_id => $match) {
				if(isset($changes[$matches[0][$match_id]])) {
					continue;
				}
				
				$label = trim($match,'_');
				$placeholder = 'prompt_' . DevblocksPlatform::strAlphaNum($label,'_','_');
				
				// Multiple line
				if(substr($match,0,2) == '__') {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_MULTI_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => '',
					);
					
				// Single line w/ default
				} else if(substr($match,0,1) == '_') {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => $label,
					);
					
				// Single line
				} else {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => '',
					);
					
				}
				
				$content = str_replace($matches[0][$match_id], '{{' . $placeholder . '}}', $content);
				
				$changes[$matches[0][$match_id]] = $placeholder;
			}
			
			if(!empty($placeholders)) {
				$db->Execute(sprintf("UPDATE snippet SET content = %s, custom_placeholders_json = %s WHERE id = %d",
					$db->qstr($content),
					$db->qstr(json_encode($placeholders)),
					$row['id']
				));
			}
		}
	}
}

// ===========================================================================
// Finish up

return TRUE;
