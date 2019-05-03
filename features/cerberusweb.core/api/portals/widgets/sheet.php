<?php
class PortalWidget_Sheet extends Extension_PortalWidget {
	const ID = 'cerb.portal.widget.sheet';
	
	public function renderConfig(Model_PortalWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/sheet/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		return true;
	}
	
	private function _renderSheet(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict, array $params = []) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$sheets = DevblocksPlatform::services()->sheet()->newInstance();
		
		@$data_query = $widget->params['data_query'];
		@$sheet_yaml = $widget->params['sheet_yaml'];
		
		$error = null;
		
		if(false == ($data_query = $tpl_builder->build($data_query, $dict)))
			return;
		
		if(array_key_exists('page', $params)) {
			$data_query .= sprintf(' page:%d', $params['page']);
		}
		
		if(false == ($results = DevblocksPlatform::services()->data()->executeQuery($data_query, $error)))
			return;
		
		if(false == ($sheet = $sheets->parseYaml($sheet_yaml, $error)))
			return;
		
		$hide_headings = array_key_exists('headings', $sheet) && !$sheet['headings'] ? true : false;
		$tpl->assign('hide_headings', $hide_headings);
		
		$sheets->addType('custom', $sheets->types()->custom());
		$sheets->addType('date', $sheets->types()->date());
		$sheets->addType('link', $sheets->types()->link());
		$sheets->addType('slider', $sheets->types()->slider());
		$sheets->addType('text', $sheets->types()->text());
		$sheets->setDefaultType('text');
		
		$sheet_dicts = $results['data'];
		
		$rows = $sheets->getRows($sheet, $sheet_dicts);
		$tpl->assign('rows', $rows);
		
		$columns = $sheet['columns'];
		$tpl->assign('columns', $columns);
		
		$tpl->assign('widget', $widget);
		
		@$paging = $results['_']['paging'];
		
		if($paging) {
			$tpl->assign('paging', $paging);
		}
		
		if('fieldsets' == @$sheet['style']) {
			$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/sheet/sheet_fieldsets.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/sheet/sheet_table.tpl');
		}
	}
	
	public function render(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict) {
		@$page = DevblocksPlatform::importGPC($_POST['page'], 'integer', 0);
		$render_params = [];
		
		if($page)
			$render_params['page'] = $page;
		
		$this->_renderSheet($widget, $dict, $render_params);
	}
}