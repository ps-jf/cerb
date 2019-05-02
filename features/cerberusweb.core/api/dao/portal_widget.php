<?php
class DAO_PortalWidget extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const PORTAL_PAGE_ID = 'portal_page_id';
	const POS = 'pos';
	const UPDATED_AT = 'updated_at';
	const WIDTH_UNITS = 'width_units';
	const ZONE = 'zone';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->addValidator($validation->validators()->extension('Extension_PortalWidget'))
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
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
			->addField(self::PORTAL_PAGE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_PORTAL_PAGE))
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
			->addField(self::WIDTH_UNITS)
			->number()
			->setMin(1)
			->setMax(255)
			;
		$validation
			->addField(self::ZONE)
			->string()
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
		
		$sql = "INSERT INTO portal_widget () VALUES ()";
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
			
		$context = "cerb.contexts.portal.widget";
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
			parent::_update($batch_ids, 'portal_widget', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.portal_widget.update',
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
		parent::_updateWhere('portal_widget', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = 'cerb.contexts.portal.widget';
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_PortalWidget[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, extension_id, portal_page_id, name, updated_at, params_json, width_units, zone, pos ".
			"FROM portal_widget ".
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
	 * @return Model_PortalWidget[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, [DAO_PortalWidget::POS,DAO_PortalWidget::NAME], [true,true], null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_PortalWidget	 */
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
	 * @return Model_PortalWidget[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	static function getByPortalPageId($page_id) {
		$widgets = self::getAll();
		
		return array_filter($widgets, function($widget) use ($page_id) {
			if($widget->portal_page_id == $page_id)
				return true;
			
			return false;
		});
	}
	
	/**
	 * @param resource $rs
	 * @return Model_PortalWidget[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_PortalWidget();
			$object->id = $row['id'];
			$object->extension_id = $row['extension_id'];
			$object->portal_page_id = $row['portal_page_id'];
			$object->name = $row['name'];
			$object->updated_at = $row['updated_at'];
			$object->width_units = $row['width_units'];
			$object->zone = $row['zone'];
			$object->pos = $row['pos'];
			
			if(false != (@$params = json_decode($row['params_json'], true)))
				$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('portal_widget');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM portal_widget WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerb.contexts.portal.widget',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_PortalWidget::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_PortalWidget', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"portal_widget.id as %s, ".
			"portal_widget.extension_id as %s, ".
			"portal_widget.portal_page_id as %s, ".
			"portal_widget.name as %s, ".
			"portal_widget.updated_at as %s, ".
			"portal_widget.params_json as %s, ".
			"portal_widget.width_units as %s, ".
			"portal_widget.zone as %s, ".
			"portal_widget.pos as %s ",
				SearchFields_PortalWidget::ID,
				SearchFields_PortalWidget::EXTENSION_ID,
				SearchFields_PortalWidget::PORTAL_PAGE_ID,
				SearchFields_PortalWidget::NAME,
				SearchFields_PortalWidget::UPDATED_AT,
				SearchFields_PortalWidget::PARAMS_JSON,
				SearchFields_PortalWidget::WIDTH_UNITS,
				SearchFields_PortalWidget::ZONE,
				SearchFields_PortalWidget::POS
			);
			
		$join_sql = "FROM portal_widget ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_PortalWidget');
	
		return array(
			'primary_table' => 'portal_widget',
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
			$object_id = intval($row[SearchFields_PortalWidget::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(portal_widget.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_PortalWidget extends DevblocksSearchFields {
	const ID = 'p_id';
	const EXTENSION_ID = 'p_extension_id';
	const PORTAL_PAGE_ID = 'p_portal_page_id';
	const NAME = 'p_name';
	const UPDATED_AT = 'p_updated_at';
	const PARAMS_JSON = 'p_params_json';
	const WIDTH_UNITS = 'p_width_units';
	const ZONE = 'p_zone';
	const POS = 'p_pos';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'portal_widget.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_PORTAL_WIDGET => new DevblocksSearchFieldContextKeys('portal_widget.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, 'cerb.contexts.portal.widget', self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr('cerb.contexts.portal.widget')), self::getPrimaryKey());
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
			case SearchFields_PortalWidget::ID:
				$models = DAO_PortalWidget::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'portal_widget', 'id', $translate->_('common.id'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'portal_widget', 'extension_id', $translate->_('common.extension'), null, true),
			self::PORTAL_PAGE_ID => new DevblocksSearchField(self::PORTAL_PAGE_ID, 'portal_widget', 'portal_page_id', $translate->_('common.page'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'portal_widget', 'name', $translate->_('common.name'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'portal_widget', 'updated_at', $translate->_('common.updated'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'portal_widget', 'params_json', $translate->_('common.params'), null, true),
			self::WIDTH_UNITS => new DevblocksSearchField(self::WIDTH_UNITS, 'portal_widget', 'width_units', $translate->_('common.width'), null, true),
			self::ZONE => new DevblocksSearchField(self::ZONE, 'portal_widget', 'zone', $translate->_('common.zone'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'portal_widget', 'pos', $translate->_('common.order'), null, true),

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

class Model_PortalWidget {
	public $id;
	public $extension_id;
	public $portal_page_id;
	public $name;
	public $updated_at;
	public $params = [];
	public $width_units;
	public $zone;
	public $pos;
	
	/**
	 * @return Extension_PortalWidget
	 */
	function getExtension($as_instance=true) {
		return Extension_PortalWidget::get($this->extension_id, $as_instance);
	}
	
	function getPage() {
		if(!$this->portal_page_id)
			return null;
		
		return DAO_PortalPage::get($this->portal_page_id);
	}
	
	function render(DevblocksDictionaryDelegate $dict) {
		if(false == ($widget_ext = $this->getExtension(true)))
			return;
		
		$widget_ext->render($this, $dict);
	}
};

class View_PortalWidget extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'portal_widgets';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.portal.widget');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_PortalWidget::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_PortalWidget::NAME,
			SearchFields_PortalWidget::EXTENSION_ID,
			SearchFields_PortalWidget::PORTAL_PAGE_ID,
			SearchFields_PortalWidget::UPDATED_AT,
			SearchFields_PortalWidget::WIDTH_UNITS,
			SearchFields_PortalWidget::ZONE,
			SearchFields_PortalWidget::POS,
		);
		$this->addColumnsHidden(array(
			SearchFields_PortalWidget::PARAMS_JSON,
			SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK,
			SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET,
			SearchFields_PortalWidget::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_PortalWidget::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_PortalWidget');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_PortalWidget', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_PortalWidget', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK:
				case SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET:
				case SearchFields_PortalWidget::VIRTUAL_WATCHERS:
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
		$context = 'cerb.contexts.portal.widget';

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_PortalWidget::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_PortalWidget::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . 'cerb.contexts.portal.widget'],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PortalWidget::ID),
					'examples' => [
						['type' => 'chooser', 'context' => 'cerb.contexts.portal.widget', 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'page.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PortalWidget::PORTAL_PAGE_ID),
					'examples' => [
						['type' => 'chooser', 'context' => 'cerb.contexts.portal.page', 'q' => ''],
					]
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PortalWidget::EXTENSION_ID),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_PortalWidget::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_PortalWidget::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('cerb.contexts.portal.widget', $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext('cerb.contexts.portal.widget');
		$tpl->assign('custom_fields', $custom_fields);
		
		// Profile pages
		// [TODO] Do this with extract + lazy load in view
		$portal_pages = DAO_PortalPage::getAll();
		$tpl->assign('portal_pages', $portal_pages);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/portals/widgets/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_PortalWidget::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_PortalWidget::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_PortalWidget::ID:
			case SearchFields_PortalWidget::EXTENSION_ID:
			case SearchFields_PortalWidget::PORTAL_PAGE_ID:
			case SearchFields_PortalWidget::NAME:
			case SearchFields_PortalWidget::UPDATED_AT:
			case SearchFields_PortalWidget::PARAMS_JSON:
			case SearchFields_PortalWidget::WIDTH_UNITS:
			case SearchFields_PortalWidget::ZONE:
			case SearchFields_PortalWidget::POS:
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_PortalWidget::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_PortalWidget::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
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

class Context_PortalWidget extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.portal.widget';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}

	function getRandom() {
		return DAO_PortalWidget::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=portal_widget&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_PortalWidget();
		
		$properties['name'] = array(
			'name' => mb_ucfirst($translate->_('common.name')),
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
		$portal_widget = DAO_PortalWidget::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($portal_widget->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $portal_widget->id,
			'name' => $portal_widget->name,
			'permalink' => $url,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($portal_widget, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Portal Widget:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerb.contexts.portal.widget');

		// Polymorph
		if(is_numeric($portal_widget)) {
			$portal_widget = DAO_PortalWidget::get($portal_widget);
		} elseif($portal_widget instanceof Model_PortalWidget) {
			// It's what we want already.
		} elseif(is_array($portal_widget)) {
			$portal_widget = Cerb_ORMHelper::recastArrayToModel($portal_widget, 'Model_PortalWidget');
		} else {
			$portal_widget = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'pos' => $prefix.$translate->_('common.order'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'width_units' => $prefix.$translate->_('common.width'),
			'zone' => $prefix.$translate->_('common.zone'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'extension_id' => 'extension',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'pos' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'width_units' => Model_CustomField::TYPE_NUMBER,
			'zone' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = 'cerb.contexts.portal.widget';
		$token_values['_types'] = $token_types;
		
		if($portal_widget) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $portal_widget->name;
			$token_values['extension_id'] = $portal_widget->extension_id;
			$token_values['id'] = $portal_widget->id;
			$token_values['name'] = $portal_widget->name;
			$token_values['pos'] = $portal_widget->pos;
			$token_values['updated_at'] = $portal_widget->updated_at;
			$token_values['width_units'] = $portal_widget->width_units;
			$token_values['zone'] = $portal_widget->zone;
			
			$token_values['page_id'] = $portal_widget->portal_page_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($portal_widget, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=portal_widget&id=%d-%s",$portal_widget->id, DevblocksPlatform::strToPermalink($portal_widget->name)), true);
		}
		
		// Portal page
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_PORTAL_PAGE, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'page_',
			$prefix.'Page:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_PortalWidget::ID,
			'links' => '_links',
			'name' => DAO_PortalWidget::NAME,
			'updated_at' => DAO_PortalWidget::UPDATED_AT,
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
		
		$context = 'cerb.contexts.portal.widget';
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
		$view->name = 'Portal Widget';
		/*
		$view->addParams(array(
			SearchFields_PortalWidget::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_PortalWidget::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_PortalWidget::UPDATED_AT;
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
		$view->name = 'Portal Widget';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_PortalWidget::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = 'cerb.contexts.portal.widget';
		
		if(!empty($context_id)) {
			$model = DAO_PortalWidget::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(empty($context_id))
				$model = new Model_PortalWidget();
			
			if(!$context_id) {
				if(!empty($edit)) {
					$tokens = explode(' ', trim($edit));
					
					foreach($tokens as $token) {
						@list($k,$v) = explode(':', $token);
						
						if($v)
						switch($k) {
							case 'page':
								$model->portal_page_id = intval($v);
								break;
						}
					}
				} else {
					if(false != ($view = C4_AbstractViewLoader::getView($view_id))) {
						$filters = $view->findParam(SearchFields_PortalWidget::PORTAL_PAGE_ID, $view->getParams());
						
						if(!empty($filters)) {
							$filter = array_shift($filters);
							if(is_numeric($filter->value))
								$model->portal_page_id = $filter->value;
						}
					}
				}
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Widget extensions
			$widget_extensions = Extension_PortalWidget::getAll(false);
			$tpl->assign('widget_extensions', $widget_extensions);
			
			// Placeholder menu
			
			if(isset($model)) {
				$labels = $values = [];
				
				// Identity dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_IDENTITY, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('identity_', 'Identity ', $merge_labels, $merge_values, $labels, $values);
				
				// Page dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_PORTAL_PAGE, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('page_', 'Page ', $merge_labels, $merge_values, $labels, $values);
				
				// Portal dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_PORTAL, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('portal_', 'Portal ', $merge_labels, $merge_values, $labels, $values);
				
				$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
				$tpl->assign('placeholders', $placeholders);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->assign('model', $model);
			$tpl->display('devblocks:cerberusweb.core::internal/portals/widgets/peek_edit.tpl');
			
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
			
			$tpl->display('devblocks:cerberusweb.core::internal/portals/widgets/peek.tpl');
		}
	}
};

