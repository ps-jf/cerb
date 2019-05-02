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

class PageSection_ProfilesPortalPage extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // portal_page 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = 'cerb.contexts.portal.page';
		
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerb.contexts.portal.page')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_PortalPage::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
				@$is_private = DevblocksPlatform::importGPC($_REQUEST['is_private'], 'integer', 0);
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'], 'integer', 0);
				@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'], 'integer', 0);
				@$uri = DevblocksPlatform::importGPC($_REQUEST['uri'], 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_PortalPage::EXTENSION_ID => $extension_id,
						DAO_PortalPage::IS_PRIVATE => $is_private,
						DAO_PortalPage::NAME => $name,
						DAO_PortalPage::PARAMS_JSON => json_encode($params),
						DAO_PortalPage::PORTAL_ID => $portal_id,
						DAO_PortalPage::POS => $pos,
						DAO_PortalPage::UPDATED_AT => time(),
						DAO_PortalPage::URI => $uri,
					);
					
					if(false == ($extension = Extension_PortalPage::get($extension_id)))
						throw new Exception_DevblocksAjaxValidationError("Invalid portal page type.");
					
					if(!$extension->saveConfig($fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_PortalPage::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PortalPage::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_PortalPage::create($fields);
					DAO_PortalPage::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, 'cerb.contexts.portal.page', $id);
					
				} else { // Edit
					$fields = array(
						DAO_PortalPage::IS_PRIVATE => $is_private,
						DAO_PortalPage::NAME => $name,
						DAO_PortalPage::PARAMS_JSON => json_encode($params),
						DAO_PortalPage::POS => $pos,
						DAO_PortalPage::UPDATED_AT => time(),
						DAO_PortalPage::URI => $uri,
					);
					
					if(false == ($page = DAO_PortalPage::get($id)))
						throw new Exception_DevblocksAjaxValidationError("This portal page no longer exists.");
					
					if(false == ($extension = $page->getExtension(true)))
						throw new Exception_DevblocksAjaxValidationError("Invalid portal page type.");
					
					if(!$extension->saveConfig($fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_PortalPage::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PortalPage::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_PortalPage::update($id, $fields);
					DAO_PortalPage::onUpdateByActor($active_worker, $id, $fields);
					
				}
	
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost('cerb.contexts.portal.page', $id, $field_ids, $error))
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
	
	function renderPageConfigAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'], 'string', '');
		
		if(false == ($extension = Extension_PortalPage::get($extension_id)))
			return;
		
		$model = new Model_PortalPage();
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=portal_page', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=portal_page&id=%d-%s", $row[SearchFields_PortalPage::ID], DevblocksPlatform::strToPermalink($row[SearchFields_PortalPage::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_PortalPage::ID],
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
