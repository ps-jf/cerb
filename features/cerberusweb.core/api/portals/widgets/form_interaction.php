<?php
class PortalWidget_FormInteraction extends Extension_PortalWidget {
	const ID = 'cerb.portal.widget.form_interaction';
	
	public function renderConfig(Model_PortalWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/form_interaction/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		if(!array_key_exists(DAO_PortalPage::PARAMS_JSON, $fields)) {
			$error = 'Portal page parameters are required.';
			return false;
		}
		
		if(false === ($params = json_decode($fields[DAO_PortalPage::PARAMS_JSON], true))) {
			$error = 'Unable to read portal parameters.';
			return false;
		}
		
		if(false == ($behavior_id = @$params['behavior_id'])) {
			$error = 'A form builder behavior is required.';
			return false;
		}
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id))) {
			$error = 'The selected form builder behavior does not exist.';
			return false;
		}
		
		if($behavior->event_point != Event_FormInteractionPortal::ID) {
			$error = 'The selected behavior is not a form builder.';
			return false;
		}
		
		return true;
	}
	
	public function render(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		$session = ChPortalHelper::getSession();
		
		$is_refresh = 'POST' == DevblocksPlatform::strUpper(DevblocksPlatform::getHttpMethod());
		
		// Reset the session
		if(array_key_exists('reset', $_POST)) {
			$state_key = $this->_getStateKey($widget, $dict);
			$session->setProperty($state_key, null);
		}
		
		$tpl->assign('dict', $dict);
		
		$page = $widget->getPage();
		$tpl->assign('page', $page);
		
		$tpl->assign('widget', $widget);
		$tpl->assign('widget_ext', $this);
		$tpl->assign('is_refresh', $is_refresh);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/form_interaction.tpl');
	}
	
	private function _getStateKey(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict) {
		$portal = ChPortalHelper::getPortal();
		$session = ChPortalHelper::getSession();
		
		if(false == $behavior_id = @$widget->params['behavior_id'])
			return;
		
		// Unique per portal/widget/behavior/record
		$state_key = 'form:portal_widget:' . sha1(sprintf("%s:%s:%d:%d:%d:%s:%d",
			$portal->code,
			$session->session_id,
			$widget->id,
			$behavior_id,
			$dict->get('identity_id', 0),
			$dict->get('record__context', ''),
			$dict->get('record_id', 0)
		));
		
		return $state_key;
	}
	
	function renderForm(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict, $is_submit=false) {
		$session = ChPortalHelper::getSession();
		
		// Do we have a state for this form by this session?
		
		$state_key = $this->_getStateKey($widget, $dict);
		
		// New state
		if(null == ($state_id = $session->getProperty($state_key, null))) {
			$interaction = $this->_startFormSession($widget, $dict);
			
			$session->setProperty($state_key, $interaction->session_id);
			
		// Resuming
		} else {
			// If the session no longer exists, reset it
			if(false == ($interaction = DAO_BotSession::get($state_id))) {
				$interaction = $this->_startFormSession($widget, $dict);
				$session->setProperty($state_key, $interaction->session_id);
			}
		}
		
		$this->_renderFormState($interaction, $state_key, $is_submit);
	}
	
	private function _startFormSession(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict) {
		$session = ChPortalHelper::getSession();
		$portal = ChPortalHelper::getPortal();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(false == $interaction_behavior_id = @$widget->params['behavior_id'])
			return;
		
		if(false == ($interaction_behavior = DAO_TriggerEvent::get($interaction_behavior_id)))
			return;
		
		if(
			!$interaction_behavior
			|| $interaction_behavior->event_point != Event_FormInteractionPortal::ID
		)
			return false;
		
		// Start the session using the behavior
		
		$client_ip = DevblocksPlatform::getClientIp();
		$client_platform = '';
		$client_browser = '';
		$client_browser_version = '';
		
		if(false !== ($client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
			$client_platform = @$client_user_agent_parts['platform'] ?: '';
			$client_browser = @$client_user_agent_parts['browser'] ?: '';
			$client_browser_version = @$client_user_agent_parts['version'] ?: '';
		}
		
		// Load variables into initial dictionary
		
		@$behavior_vars = DevblocksPlatform::importVar(@$widget->params['behavior_vars'], 'array', []);
		
		$behavior_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(is_array($behavior_vars))
		foreach($behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!array_key_exists($k, $interaction_behavior->variables))
					continue;
				
				if(false == ($value = $tpl_builder->build($v, $dict)))
					$value = null;
				
				$value = $interaction_behavior->formatVariable($interaction_behavior->variables[$k], $value, $behavior_dict);
				$behavior_dict->set($k, $value);
			}
		}
		
		$session_data = [
			'behavior_id' => $interaction_behavior->id,
			'dict' => $behavior_dict->getDictionary(null, false),
			'cookie' => $session->session_id,
			'portal_id' => $portal->id,
			'client_browser' => $client_browser,
			'client_browser_version' => $client_browser_version,
			'client_ip' => $client_ip,
			'client_platform' => $client_platform,
		];
		
		$created_at = time();
		
		$session_id = DAO_BotSession::create([
			DAO_BotSession::SESSION_DATA => json_encode($session_data),
			DAO_BotSession::UPDATED_AT => $created_at,
		]);
		
		$model = new Model_BotSession();
		$model->session_id = $session_id;
		$model->session_data = $session_data;
		$model->updated_at = $created_at;
		
		return $model;
	}
	
	private function _prepareResumeDecisionTree(Model_TriggerEvent $behavior, $prompts, &$interaction, &$actions, DevblocksDictionaryDelegate &$dict, &$resume_path) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		unset($interaction->session_data['form_validation_errors']);
		
		// Do we have special prompt handling instructions?
		if(array_key_exists('form_state', $interaction->session_data)) {
			$form_state = $interaction->session_data['form_state'];
			$validation_errors = [];
			
			foreach($form_state as $form_element) {
				if(!array_key_exists('_prompt', $form_element))
					continue;
				
				$prompt_var = $form_element['_prompt']['var'];
				$prompt_value = @$prompts[$prompt_var] ?: null;
				
				// If we lazy loaded a sub dictionary on the last attempt, clear it
				if(DevblocksPlatform::strEndsWith($prompt_var, '_id'))
					$dict->scrubKeys(substr($prompt_var, 0, -2));
				
				// Prompt-specific options
				switch(@$form_element['_action']) {
					case 'prompt.captcha':
						$otp_key = $prompt_var . '__otp';
						$otp = $dict->get($otp_key);
						
						if(!$otp || 0 !== strcasecmp($otp, $prompt_value)) {
							$validation_errors[] = 'Your CAPTCHA text does not match the image.';
							
							// Re-generate the challenge
							$otp = CerberusApplication::generatePassword(4);
							$dict->set($otp_key, $otp);
						}
						break;
						
					case 'prompt.checkboxes':
						if(is_null($prompt_value))
							$prompt_value = [];
						break;
						
					case 'prompt.file':
						if(!DevblocksPlatform::strEndsWith($prompt_var, '_id'))
							break;
							
						$dict->set(substr($prompt_var,0,-2) . '_context', CerberusContexts::CONTEXT_ATTACHMENT);
						break;
				}
				
				$dict->set($prompt_var, $prompt_value);
				
				if(false != (@$format_tpl = $form_element['_prompt']['format'])) {
					$var_message = $tpl_builder->build($format_tpl, $dict);
					$dict->set($prompt_var, $var_message);
				}
				
				if(false != (@$validate_tpl = $form_element['_prompt']['validate'])) {
					$validation_result = trim($tpl_builder->build($validate_tpl, $dict));
					
					if(!empty($validation_result)) {
						$validation_errors[] = $validation_result;
					}
				}
			}
			
			$interaction->session_data['form_validation_errors'] = $validation_errors;
		}
		
		return true;
	}
	
	private function _renderFormState(Model_BotSession $interaction, $state_key, $is_submit=false) {
		$tpl = DevblocksPlatform::services()->template();
		
		$identity = ChPortalHelper::getIdentity();
		
		@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
		
		// Load our default behavior for this interaction
		if(false == (@$behavior_id = $interaction->session_data['behavior_id']))
			return false;
		
		$actions = [];
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		$event_params = [
			'prompts' => $prompts,
			'actions' => &$actions,
			
			'identity_id' => $identity ? $identity->id : 0,
			'behavior_id' => $behavior_id,
			'portal_id' => @$interaction->session_data['portal_id'],
			
			'cookie' => @$interaction->session_data['cookie'],
			'client_browser' => @$interaction->session_data['client_browser'],
			'client_browser_version' => @$interaction->session_data['client_browser_version'],
			'client_ip' => @$interaction->session_data['client_ip'],
			'client_platform' => @$interaction->session_data['client_platform'],
		];
		
		$event_model = new Model_DevblocksEvent(
			Event_FormInteractionPortal::ID,
			$event_params
		);
		
		if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
			return;
		
		if(!($event instanceof Event_FormInteractionPortal))
			return;
		
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Are we resuming a scope?
		$resume_dict = @$interaction->session_data['dict'];
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		$behavior_dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = @$interaction->session_data['path'];
		$result = [];
		
		if($resume_path) {
			// Did we try to submit?
			if($is_submit) {
				$this->_prepareResumeDecisionTree($behavior, $prompts, $interaction, $actions, $behavior_dict, $resume_path);
				
				$form_validation_errors = $interaction->session_data['form_validation_errors'];
				
				if($form_validation_errors) {
					$tpl->assign('errors', $form_validation_errors);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_errors.tpl');
					
					// If we had validation errors, repeat the form state
					$actions = $interaction->session_data['form_state'];
					
					// Simulate the end state
					$result = [
						'exit_state' => 'SUSPEND',
						'path' => $resume_path,
					];
					
				} else {
					if(false == ($result = $behavior->resumeDecisionTree($behavior_dict, false, $event, $resume_path)))
						return;
				}
				
			// Re-render without changing state
			} else {
				// If we had validation errors, repeat the form state
				$actions = $interaction->session_data['form_state'];
				$exit_state = $interaction->session_data['exit_state'];
				
				// Simulate the end state
				$result = [
					'exit_state' => $exit_state,
					'path' => $resume_path,
				];
			}
			
		} else {
			if(false == ($result = $behavior->runDecisionTree($behavior_dict, false, $event)))
				return;
		}
		
		$values = $behavior_dict->getDictionary(null, false);
		$values = array_diff_key($values, $event->getValues());
		
		$interaction->session_data['dict'] = $values;
		$interaction->session_data['path'] = $result['path'];
		$interaction->session_data['form_state'] = $actions;
		$interaction->session_data['exit_state'] = $result['exit_state'];
		
		foreach($actions as $params) {
			switch(@$params['_action']) {
				case 'prompt.captcha':
					$captcha = DevblocksPlatform::services()->captcha();
					
					@$label = $params['label'];
					@$var = $params['_prompt']['var'];
					
					$otp_key = $var . '__otp';
					$otp = $behavior_dict->get($otp_key);
					
					$image_bytes = $captcha->createImage($otp);
					$tpl->assign('image_bytes', $image_bytes);
					
					$tpl->assign('label', $label);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_captcha.tpl');
					break;
					
				case 'prompt.checkboxes':
					@$label = $params['label'];
					@$options = $params['options'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('options', $options);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_checkboxes.tpl');
					break;
					
				case 'prompt.radios':
					@$label = $params['label'];
					@$style = $params['style'];
					@$orientation = $params['orientation'];
					@$options = $params['options'];
					@$default = $params['default'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('orientation', $orientation);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					
					if($style == 'buttons') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_buttons.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_radios.tpl');
					}
					break;
					
				case 'prompt.text':
					@$label = $params['label'];
					@$placeholder = $params['placeholder'];
					@$default = $params['default'];
					@$mode = $params['mode'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_text.tpl');
					break;
					
				case 'respond.text':
					if(false == ($msg = @$params['message']))
						break;
					
					$tpl->assign('message', $msg);
					$tpl->assign('format', @$params['format']);
					$tpl->assign('style', @$params['style']);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_text.tpl');
					break;
					
				case 'respond.sheet':
					$sheets = DevblocksPlatform::services()->sheet()->newInstance();
					
					$error = null;
					
					// [TODO] Error handling
					
					$results = DevblocksPlatform::services()->data()->executeQuery($params['data_query'], $error);
					
					$sheet = $sheets->parseYaml($params['sheet_yaml'], $error);
					
					$sheets->addType('custom', $sheets->types()->custom());
					$sheets->addType('date', $sheets->types()->date());
					$sheets->addType('link', $sheets->types()->link());
					$sheets->addType('slider', $sheets->types()->slider());
					$sheets->addType('text', $sheets->types()->text());
					$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
					$sheets->setDefaultType('text');
					
					$sheet_dicts = $results['data'];
					
					$rows = $sheets->getRows($sheet, $sheet_dicts);
					$tpl->assign('rows', $rows);
					
					$columns = $sheet['columns'];
					$tpl->assign('columns', $columns);
					
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_sheet.tpl');
					break;
					
				default:
					$tpl->assign('continue_options', [
						'continue' => true,
						'reset' => true,
					]);
					$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/form_interaction/continue.tpl');
					break;
			}
		}
		
		if($result['exit_state'] != 'SUSPEND') {
			$tpl->assign('continue_options', [
				'continue' => false,
				'reset' => true,
			]);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/form_interaction/continue.tpl');
		}
		
		// Save session scope
		DAO_BotSession::update($interaction->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($interaction->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
	}
};