<?php
class DAO_PortalPage extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const IS_PRIVATE = 'is_private';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const PORTAL_ID = 'portal_id';
	const POS = 'pos';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->addValidator($validation->validators()->extension('Extension_PortalPage'))
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_PRIVATE)
			->bit()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777216)
			;
		$validation	
			->addField(self::PORTAL_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_PORTAL))
			->setRequired(true)
			;
		$validation	
			->addField(self::POS)
			->number()
			->setMin(0)
			->setMax(99)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->setRequired(true)
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO portal_page () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = "cerb.contexts.portal.page";
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'portal_page', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.portal_page.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('portal_page', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = 'cerb.contexts.portal.page';
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_PortalPage[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, extension_id, params_json, portal_id, is_private, pos, updated_at, uri ".
			"FROM portal_page ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_PortalPage[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, [DAO_PortalPage::POS, DAO_PortalPage::NAME], [true, true], null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_PortalPage	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_PortalPage[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * 
	 * @param integer $portal_id
	 * @return Model_PortalPage[]
	 */
	static function getByPortalId($portal_id) {
		$pages = self::getAll();
		
		return array_filter($pages, function($page) use ($portal_id) {
			if($page->portal_id == $portal_id)
				return true;
			
			return false;
		});
	}
	
	/**
	 * 
	 * @param integer $portal_id
	 * @param string $uri
	 * @return Model_PortalPage|NULL
	 */
	static function getByPortalUri($portal_id, $uri) {
		$pages = self::getByPortalId($portal_id);
		
		$page_uris = array_column($pages, 'uri', 'id');
		
		$ids = array_keys($page_uris, $uri);
		
		if(!empty($ids)) {
			$id = current($ids);
			return $pages[$id];
		}
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_PortalPage[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_PortalPage();
			$object->id = intval($row['id']);
			$object->is_private = $row['is_private'] ? 1 : 0;
			$object->name = $row['name'];
			$object->extension_id = $row['extension_id'];
			$object->portal_id = intval($row['portal_id']);
			$object->pos = intval($row['pos']);
			$object->updated_at = intval($row['updated_at']);
			$object->uri = $row['uri'];
			
			if(false != (@$params = json_decode($row['params_json'], true)))
				$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('portal_page');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM portal_page WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerb.contexts.portal.page',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_PortalPage::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_PortalPage', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"portal_page.id as %s, ".
			"portal_page.name as %s, ".
			"portal_page.extension_id as %s, ".
			"portal_page.portal_id as %s, ".
			"portal_page.is_private as %s, ".
			"portal_page.pos as %s, ".
			"portal_page.uri as %s, ".
			"portal_page.updated_at as %s ",
				SearchFields_PortalPage::ID,
				SearchFields_PortalPage::NAME,
				SearchFields_PortalPage::EXTENSION_ID,
				SearchFields_PortalPage::PORTAL_ID,
				SearchFields_PortalPage::IS_PRIVATE,
				SearchFields_PortalPage::POS,
				SearchFields_PortalPage::URI,
				SearchFields_PortalPage::UPDATED_AT
			);
			
		$join_sql = "FROM portal_page ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_PortalPage');
	
		return array(
			'primary_table' => 'portal_page',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_PortalPage::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(portal_page.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_PortalPage extends DevblocksSearchFields {
	const ID = 'p_id';
	const IS_PRIVATE = 'p_is_private';
	const NAME = 'p_name';
	const EXTENSION_ID = 'p_extension_id';
	const PORTAL_ID = 'p_portal_id';
	const POS = 'p_pos';
	const UPDATED_AT = 'p_updated_at';
	const URI = 'p_uri';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'portal_page.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_PortalPage::ID => new DevblocksSearchFieldContextKeys('portal_page.id', self::ID),
			Context_CommunityTool::ID => new DevblocksSearchFieldContextKeys('portal_page.portal_id', self::PORTAL_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, 'cerb.contexts.portal.page', self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr('cerb.contexts.portal.page')), self::getPrimaryKey());
				break;
			
			/*
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, '', self::getPrimaryKey());
				break;
			*/
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_PortalPage::EXTENSION_ID:
				$extensions = Extension_PortalPage::getAll(false);
				return array_column(DevblocksPlatform::objectsToArrays($extensions), 'name', 'id');
				break;
			
			case SearchFields_PortalPage::ID:
				$models = DAO_PortalPage::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_PortalPage::PORTAL_ID:
				$models = DAO_CommunityTool::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'portal_page', 'id', $translate->_('common.id'), null, true),
			self::IS_PRIVATE => new DevblocksSearchField(self::IS_PRIVATE, 'portal_page', 'is_private', $translate->_('common.is_private'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'portal_page', 'name', $translate->_('common.name'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'portal_page', 'extension_id', $translate->_('common.extension'), null, true),
			self::PORTAL_ID => new DevblocksSearchField(self::PORTAL_ID, 'portal_page', 'portal_id', $translate->_('common.portal'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'portal_page', 'pos', $translate->_('common.order'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'portal_page', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'portal_page', 'uri', $translate->_('common.uri'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_PortalPage {
	public $id;
	public $is_private;
	public $name;
	public $extension_id;
	public $params = [];
	public $portal_id;
	public $pos = 0;
	public $updated_at;
	public $uri;
	
	/**
	 * @return Extension_PortalPage
	 */
	function getExtension($as_instance=true) {
		return Extension_PortalPage::get($this->extension_id, $as_instance);
	}
	
	function render(Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		if(false == ($page_extension = $this->getExtension()))
			return;
		
		$page_extension->render($this, $portal, $response);
	}
	
	function getUrlQuery() {
		$url_writer = DevblocksPlatform::services()->url();
		$query = $url_writer->arrayToQuery(explode('/', $this->uri));
		return $query;
	}
	
	function isVisibleByIdentity(Model_Identity $identity=null) {
		if(!$this->is_private)
			return true;
		
		if(!$identity)
			return false;
		
		return true;
	}
};

class View_PortalPage extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'portal_pages';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.portal.page');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_PortalPage::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_PortalPage::NAME,
			SearchFields_PortalPage::PORTAL_ID,
			SearchFields_PortalPage::EXTENSION_ID,
			SearchFields_PortalPage::URI,
			SearchFields_PortalPage::IS_PRIVATE,
			SearchFields_PortalPage::POS,
			SearchFields_PortalPage::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK,
			SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET,
			SearchFields_PortalPage::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_PortalPage::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_PortalPage');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_PortalPage', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_PortalPage', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_PortalPage::EXTENSION_ID:
				case SearchFields_PortalPage::PORTAL_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK:
				case SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET:
				case SearchFields_PortalPage::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = 'cerb.contexts.portal.page';

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_PortalPage::EXTENSION_ID:
			case SearchFields_PortalPage::PORTAL_ID:
				$label_map = function($values) use ($column) {
					return SearchFields_PortalPage::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_PortalPage::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_PortalPage::getFields();
		
		$type_extensions = DevblocksPlatform::getExtensions(Extension_PortalPage::POINT, false);
		DevblocksPlatform::sortObjects($type_extensions, 'name');
		
		$types = array_column(DevblocksPlatform::objectsToArrays($type_extensions), 'name', 'id');
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalPage::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . 'cerb.contexts.portal.page'],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PortalPage::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PORTAL_PAGE, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalPage::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'portal.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PortalPage::PORTAL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PORTAL, 'q' => ''],
					]
				),
			'pos' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PortalPage::POS),
					'examples' => []
				),
			'private' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_PortalPage::IS_PRIVATE),
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalPage::EXTENSION_ID),
					'examples' => [
						['type' => 'list', 'values' => $types],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_PortalPage::UPDATED_AT),
				),
			'uri' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalPage::URI),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_PortalPage::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('cerb.contexts.portal.page', $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerb.contexts.portal.page');
		$tpl->assign('custom_fields', $custom_fields);
		
		// Portals
		$portals = DAO_CommunityTool::getAll();
		$tpl->assign('portals', $portals);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/portals/pages/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? [$param->value] : $param->value;

		switch($field) {
			case SearchFields_PortalPage::EXTENSION_ID:
			case SearchFields_PortalPage::PORTAL_ID:
				$label_map = SearchFields_PortalPage::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_PortalPage::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_PortalPage::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_PortalPage::NAME:
			case SearchFields_PortalPage::EXTENSION_ID:
			case SearchFields_PortalPage::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_PortalPage::ID:
			case SearchFields_PortalPage::PORTAL_ID:
			case SearchFields_PortalPage::POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_PortalPage::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_PortalPage::IS_PRIVATE:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_PortalPage::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_PortalPage::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_PortalPage extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.portal.page';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}

	function getRandom() {
		return DAO_PortalPage::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=portal_page&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_PortalPage();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$portal_page = DAO_PortalPage::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($portal_page->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $portal_page->id,
			'name' => $portal_page->name,
			'permalink' => $url,
			'updated' => $portal_page->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'portal_id',
			'extension_id',
			'uri',
			'is_private',
			'pos',
			'updated_at',
		);
	}
	
	function getContext($portal_page, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Portal Page:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerb.contexts.portal.page');

		// Polymorph
		if(is_numeric($portal_page)) {
			$portal_page = DAO_PortalPage::get($portal_page);
		} elseif($portal_page instanceof Model_PortalPage) {
			// It's what we want already.
		} elseif(is_array($portal_page)) {
			$portal_page = Cerb_ORMHelper::recastArrayToModel($portal_page, 'Model_PortalPage');
		} else {
			$portal_page = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'is_private' => $prefix.$translate->_('common.is_private'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'is_private' => Model_CustomField::TYPE_CHECKBOX,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = 'cerb.contexts.portal.page';
		$token_values['_types'] = $token_types;
		
		if($portal_page) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $portal_page->name;
			$token_values['is_private'] = $portal_page->is_private;
			$token_values['id'] = $portal_page->id;
			$token_values['name'] = $portal_page->name;
			$token_values['updated_at'] = $portal_page->updated_at;
			$token_values['uri'] = $portal_page->uri;
			
			$token_values['portal_id'] = $portal_page->portal_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($portal_page, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=portal_page&id=%d-%s",$portal_page->id, DevblocksPlatform::strToPermalink($portal_page->name)), true);
		}
		
		// Portal
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_PORTAL, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'portal_',
			$prefix.'Portal:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_PortalPage::ID,
			'is_private' => DAO_PortalPage::IS_PRIVATE,
			'extension_id' => DAO_PortalPage::EXTENSION_ID,
			'links' => '_links',
			'name' => DAO_PortalPage::NAME,
			'portal_id' => DAO_PortalPage::PORTAL_ID,
			'pos' => DAO_PortalPage::POS,
			'type' => DAO_PortalPage::EXTENSION_ID,
			'updated_at' => DAO_PortalPage::UPDATED_AT,
			'uri' => DAO_PortalPage::URI,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = 'cerb.contexts.portal.page';
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
		
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Portal Page';
		/*
		$view->addParams(array(
			SearchFields_PortalPage::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_PortalPage::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_PortalPage::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Portal Page';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_PortalPage::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = 'cerb.contexts.portal.page';
		
		if(!empty($context_id)) {
			$model = DAO_PortalPage::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Portal page extensions
			$page_extensions = Extension_PortalPage::getAll(false);
			$tpl->assign('page_extensions', $page_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/portals/pages/peek_edit.tpl');
			
		} else {
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							[]
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = [];
			$values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Card search buttons
			$search_buttons = $context_ext->getCardSearchButtons($dict, []);
			$tpl->assign('search_buttons', $search_buttons);
			
			$tpl->display('devblocks:cerberusweb.core::internal/portals/pages/peek.tpl');
		}
	}
};
