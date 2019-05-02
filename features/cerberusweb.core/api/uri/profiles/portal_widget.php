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
