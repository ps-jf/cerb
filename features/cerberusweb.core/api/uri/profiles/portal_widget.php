<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesPortalWidget extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // portal_widget 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = 'cerb.contexts.portal.widget';
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerb.contexts.portal.widget')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_PortalWidget::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				@$portal_page_id = DevblocksPlatform::importGPC($_REQUEST['portal_page_id'], 'integer', 0);
				@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'], 'integer', 0);
				@$zone = DevblocksPlatform::importGPC($_REQUEST['zone'], 'string', '');
				@$width_units = DevblocksPlatform::importGPC($_REQUEST['width_units'], 'integer', 0);
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_PortalWidget::EXTENSION_ID => $extension_id,
						DAO_PortalWidget::NAME => $name,
						DAO_PortalWidget::PARAMS_JSON => json_encode($params),
						DAO_PortalWidget::PORTAL_PAGE_ID => $portal_page_id,
						DAO_PortalWidget::POS => $pos,
						DAO_PortalWidget::UPDATED_AT => time(),
						DAO_PortalWidget::WIDTH_UNITS => $width_units,
						DAO_PortalWidget::ZONE => $zone,
					);
					
					if(false == ($extension = Extension_PortalWidget::get($extension_id)))
						throw new Exception_DevblocksAjaxValidationError("Invalid portal widget type.");
					
					if(!$extension->saveConfig($fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_PortalWidget::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PortalWidget::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_PortalWidget::create($fields);
					DAO_PortalWidget::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, 'cerb.contexts.portal.widget', $id);
					
				} else { // Edit
					$fields = array(
						DAO_PortalWidget::NAME => $name,
						DAO_PortalWidget::PARAMS_JSON => json_encode($params),
						DAO_PortalWidget::POS => $pos,
						DAO_PortalWidget::UPDATED_AT => time(),
						DAO_PortalWidget::WIDTH_UNITS => $width_units,
						DAO_PortalWidget::ZONE => $zone,
					);
					
					if(false == ($widget = DAO_PortalWidget::get($id)))
						throw new Exception_DevblocksAjaxValidationError("This portal widget no longer exists.");
					
					if(false == ($extension = $widget->getExtension(true)))
						throw new Exception_DevblocksAjaxValidationError("Invalid portal widget type.");
					
					if(!$extension->saveConfig($fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_PortalWidget::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PortalWidget::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_PortalWidget::update($id, $fields);
					DAO_PortalWidget::onUpdateByActor($active_worker, $id, $fields);
					
				}
	
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost('cerb.contexts.portal.widget', $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	}
	
	function renderWidgetConfigAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'], 'string', '');
		
		if(false == ($extension = Extension_PortalWidget::get($extension_id)))
			return;
		
		$model = new Model_PortalWidget();
		$model->extension_id = $extension_id;
		
		$extension->renderConfig($model);
	}
	
	function testWidgetTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'int', 0);
		@$portal_page_id = DevblocksPlatform::importGPC($_REQUEST['portal_page_id'], 'int', 0);
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		@$template_key = DevblocksPlatform::importGPC($_REQUEST['template_key'], 'string', '');
		@$index = DevblocksPlatform::importGPC($_REQUEST['index'], 'integer', 0);
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'], 'string', '');
		
		@$placeholders_yaml = DevblocksPlatform::importVar($params['placeholder_simulator_yaml'], 'string', '');
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		
		$placeholders = DevblocksPlatform::services()->string()->yamlParse($placeholders_yaml, 0);
		
		$template = null;

		if(DevblocksPlatform::strStartsWith($template_key, 'params[')) {
			$template_key = trim(substr($template_key, 6),'[]');
			$json_key = str_replace(['[',']'],['.',''],$template_key);
			$json_var = DevblocksPlatform::jsonGetPointerFromPath($params, $json_key);
			
			if(is_string($json_var)) {
				@$template = $json_var;
			} elseif (is_array($json_var)) {
				if(array_key_exists($index, $json_var)) {
					@$template = $json_var[$index];
				}
			}
		}
		
		if(false == $template)
			return;
		
		if(!$portal_page_id && $id) {
			if(false != ($portal_widget = DAO_PortalWidget::get($id))) 
				$portal_page_id = $portal_widget->portal_page_id;
		}
		
		// This may be a new widget
		if($portal_page_id) {
			$portal_page = DAO_PortalPage::get($portal_page_id);
			
		} else {
			$portal_page = null;
		}
		
		$dict = DevblocksDictionaryDelegate::instance([
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => DAO_Identity::random(),
			
			'page__context' => CerberusContexts::CONTEXT_PORTAL_PAGE,
			'page_id' => $portal_page ? intval($portal_page->id) : 0,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal_page ? intval($portal_page->portal_id) : 0,
		]);
		
		// Record ID (if page is profile)
		if($portal_page && $portal_page->extension_id = PortalPage_Profile::ID) {
			if(@$portal_page->params['context'] && false != ($portal_page_context = Extension_DevblocksContext::getByAlias($portal_page->params['context'], true))) {
				if(false != ($dao_class = $portal_page_context->getDaoClass())) {
					$dict->set('record__context', $portal_page_context->id); // $portal_page->params['context']
					$dict->set('record_id', $dao_class::random());
				}
			}
		}
		
		if(is_array($placeholders))
		foreach($placeholders as $placeholder_key => $placeholder_value) {
			$dict->set($placeholder_key, $placeholder_value);
		}
		
		$success = false;
		$output = '';
		
		if(!is_string($template) || false === (@$out = $tpl_builder->build($template, $dict))) {
			// If we failed, show the compile errors
			$errors = $tpl_builder->getErrors();
			$success = false;
			$output = @array_shift($errors);
			
		} else {
			$success = true;
			$output = $out;
		}
		
		if('json' == $format) {
			header('Content-Type: application/json; charset=utf-8');
			
			echo json_encode([
				'status' => $success,
				'response' => $output,
			]);
			
		} else {
			$tpl->assign('success', $success);
			$tpl->assign('output', $output);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = [];
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=portal_widget', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=portal_widget&id=%d-%s", $row[SearchFields_PortalWidget::ID], DevblocksPlatform::strToPermalink($row[SearchFields_PortalWidget::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_PortalWidget::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
