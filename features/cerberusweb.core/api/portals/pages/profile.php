<?php
class PortalPage_Profile extends Extension_PortalPage {
	const ID = 'cerb.portal.page.profile';
	
	public function renderConfig(Model_PortalPage $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/profile/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		if(!array_key_exists(DAO_PortalPage::PARAMS_JSON, $fields)) {
			$error = 'Portal page parameters are required.';
			return false;
		}
		
		if(false === (json_decode($fields[DAO_PortalPage::PARAMS_JSON], true))) {
			$error = 'Unable to read portal parameters.';
			return false;
		}
		
		return true;
	}
	
	private function _getDictForUri(Model_PortalPage $page, Model_CommunityTool $portal, $uri) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$identity = ChPortalHelper::getIdentity();
		
		@$context = $page->params['context'];
		@$query_required = $page->params['profile_query_records'];
		
		$values = [
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity ? $identity->id : 0,
	
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => intval($portal->id),
			
			'page__context' => CerberusContexts::CONTEXT_PORTAL_PAGE,
			'page_id' => intval($page->id),
		];
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		if(false === ($query_required = $tpl_builder->build($query_required, $dict)))
			return;
		
		if(!$context || false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;
		
		if(false == ($context_id = $context_ext->getContextIdFromAlias($uri))) {
			$context_id = intval($uri);
		}
		
		$view = $context_ext->getTempView();
		$view->addParamsRequiredWithQuickSearch($query_required);
		$view->addParamsWithQuickSearch(sprintf("id:%d", $context_id));
		$view->renderTotal = false;
		$view->renderLimit = 1;
		
		list($results,) = $view->getData();
		
		if(!array_key_exists($context_id, $results))
			return;
		
		// Add to profile dictionary
		
		$dict->set('record__context', $context);
		$dict->set('record_id', intval($context_id));
		
		return $dict;
	}
	
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		$path = $response->path;
		$uri = array_shift($path);
		
		if(false == ($dict = $this->_getDictForUri($page, $portal, $uri)))
			return;
		
		if('POST' == DevblocksPlatform::getHttpMethod()) {
			$action = array_shift($path);
			
			if($action) {
				switch($action) {
					case 'updateWidget':
						header('Content-Type: text/html; charset=utf-8');
						
						@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget'], 'integer', 0);
						
						if(false == ($widget = DAO_PortalWidget::get($widget_id)))
							exit;
						
						if($widget->portal_page_id != $page->id)
							exit;
						
						$widget->render($dict);
						break;
				}
				
				exit;
			}
		}
		
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response, $dict, $uri) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$tpl->assign('dict', $dict);
			
			// Page title
			
			@$template_page_title = $page->params['template_page_title'];
			
			if($template_page_title) {
				$page_title = $tpl_builder->build($template_page_title, $dict);
				$tpl->assign('page_title', $page_title);
			}
			
			// Widgets
			
			$widgets = DAO_PortalWidget::getByPortalPageId($page->id);
			
			// Layouts
			
			@$layout = $page->params['layout'] ?: '';
			
			$zones = [
				'content' => [],
			];
			
			switch($layout) {
				case 'sidebar_left':
					$zones = [
						'sidebar' => [],
						'content' => [],
					];
					break;
					
				case 'sidebar_right':
					$zones = [
						'content' => [],
						'sidebar' => [],
					];
					break;
					
				case 'thirds':
					$zones = [
						'left' => [],
						'center' => [],
						'right' => [],
					];
					break;
			}
	
			// Sanitize zones
			foreach($widgets as $widget_id => $widget) {
				if(array_key_exists($widget->zone, $zones)) {
					$zones[$widget->zone][$widget_id] = $widget;
					continue;
				}
				
				// If the zone doesn't exist, drop the widget into the first zone
				$zones[key($zones)][$widget_id] = $widget;
			}
			
			$tpl->assign('layout', $layout);
			$tpl->assign('zones', $zones);
			$tpl->assign('model', $page);
			
			$page_query = $page->getUrlQuery() . '&uri=' . $uri;
			$tpl->assign('page_query', $page_query);
			
			// Templates
			
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/profile.tpl');
		});
		
		parent::renderDefaultLayout($page, $portal, $renderer);
	}
}