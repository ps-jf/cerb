<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class Event_FormInteractionPortal extends Extension_DevblocksEvent {
	const ID = 'event.form.interaction.portal';
	
	/*
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		/$tpl->display('devblocks:cerberusweb.core::events/.../params.tpl');
	}
	*/
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$actions = [];
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'actions' => &$actions,
				
				'identity_id' => null,
				'cookie' => null,
				'portal_id' => null,
				'client_browser' => null,
				'client_browser_version' => null,
				'client_ip' => null,
				'client_platform' => null,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];
		
		/**
		 * Behavior
		 */
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
			
		/**
		 * Portal
		 */
		
		@$portal_id = $event_model->params['portal_id'];
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_PORTAL, $portal_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'portal_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
			
		/**
		 * Identity
		 */
		
		@$identity_id = $event_model->params['identity_id'];
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_IDENTITY, $identity_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'identity_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		// Actions
		$values['_actions'] =& $event_model->params['actions'];
		
		// Cookie
		@$cookie = $event_model->params['cookie'];
		$labels['cookie'] = 'Cookie';
		$values['cookie'] = $cookie;
		
		// Client
		@$client_browser = $event_model->params['client_browser'];
		@$client_browser_version = $event_model->params['client_browser_version'];
		@$client_ip = $event_model->params['client_ip'];
		@$client_platform = $event_model->params['client_platform'];
		
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		
		$values['client_browser'] = $client_browser;
		$values['client_browser_version'] = $client_browser_version;
		$values['client_ip'] = $client_ip;
		$values['client_platform'] = $client_platform;
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'identity_id' => array(
				'label' => 'Identity',
				'context' => CerberusContexts::CONTEXT_IDENTITY,
			),
			'identity_pool_id' => array(
				'label' => 'Identity Pool',
				'context' => CerberusContexts::CONTEXT_IDENTITY_POOL,
			),
			'portal_id' => array(
				'label' => 'Portal',
				'context' => CerberusContexts::CONTEXT_PORTAL,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		// Cookie
		$labels['cookie'] = 'Cookie';
		$types['cookie'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Client
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		
		$types['client_browser'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_browser_version'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_ip'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_platform'] = Model_CustomField::TYPE_SINGLE_LINE;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			array(
				'create_comment' => array('label' =>'Create comment'),
				'create_notification' => array('label' =>'Create notification'),
				'create_task' => array('label' =>'Create task'),
				'create_ticket' => array('label' =>'Create ticket'),
				
				'prompt_captcha' => array('label' => 'Form prompt with CAPTCHA challenge'),
				'prompt_checkboxes' => array('label' => 'Form prompt with checkboxes'),
				'prompt_radios' => array('label' => 'Form prompt with radio buttons'),
				'prompt_text' => array('label' => 'Form prompt with text input'),
				
				'prompt_submit' => array('label' => 'Form prompt with submit'),
				
				'respond_sheet' => array('label' => 'Form respond with sheet'),
				'respond_text' => array('label' => 'Form respond with text'),
				
				'send_email' => array('label' => 'Send email'),
			)
			;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment($trigger);
				break;

			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;

			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;

			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;
			
			case 'prompt_captcha':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_captcha.tpl');
				break;
				
			case 'prompt_checkboxes':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_checkboxes.tpl');
				break;
				
			case 'prompt_radios':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_radios.tpl');
				break;
				
			case 'prompt_text':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_text.tpl');
				break;
				
			case 'prompt_submit':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/action_prompt_submit.tpl');
				break;
			
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;
			
			case 'respond_text':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/responses/action_respond_text.tpl');
				break;
				
			case 'respond_sheet':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/responses/action_respond_sheet.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'worker_id');
				break;

			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'worker_id');
				break;

			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'worker_id');
				break;
			
			case 'prompt_captcha':
				$out = ">>> Prompting with CAPTCHA challenge\n";
				break;
				
			case 'prompt_checkboxes':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$label = $tpl_builder->build($params['label'], $dict);
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with checkboxes\nLabel: %s\nOptions: %s\n",
					$label,
					$options
				);
				break;
				
			case 'prompt_radios':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$label = $tpl_builder->build($params['label'], $dict);
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with radio buttons\nLabel: %s\nOptions: %s\n",
					$label,
					$options
				);
				break;
				
			case 'prompt_text':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$label = $tpl_builder->build($params['label'], $dict);
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				
				$out = sprintf(">>> Prompting with text input\nLabel: %s\nPlaceholder: %s\n",
					$label,
					$placeholder
				);
				break;
				
			case 'prompt_submit':
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			
			case 'respond_text':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['message'], $dict);
				
				$out = sprintf(">>> Sending response message\n".
					"%s\n",
					$content
				);
				break;
				
			case 'respond_sheet':
				//$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				//$query = $tpl_builder->build($params['data_query'], $dict);
				
				$out = sprintf(">>> Sending sheet as response\n"
				);
				break;
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'worker_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'worker_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
			
			case 'prompt_captcha':
				$actions =& $dict->_actions;
				
				assert($actions);
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$var = $params['var'];
				
				// [TODO] Set this based on the data set
				$label = 'Please prove you are not a robot:';
				
				// Generate random code
				$otp_key = $var . '__otp';
				$otp = $dict->get($otp_key);
				
				if(!$otp) {
					$otp = CerberusApplication::generatePassword(4);
					$dict->set($otp_key, $otp);
				}
				
				$actions[] = [
					'_action' => 'prompt.captcha',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
					],
					'label' => $label,
				];
				break;
				
			case 'prompt_checkboxes':
				$actions =& $dict->_actions;
				
				assert($actions);
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$label = $tpl_builder->build($params['label'], $dict);
				@$options = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['options'], $dict));
				@$default = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['default'], $dict));
				@$var = $params['var'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = [
					'_action' => 'prompt.checkboxes',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
						'validate' => $var_validate,
					],
					'label' => $label,
					'options' => $options,
					'default' => $default,
				];
				break;
				
			case 'prompt_radios':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$label = $tpl_builder->build($params['label'], $dict);
				@$style = $params['style'];
				@$orientation = $params['orientation'];
				@$options = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['options'], $dict));
				@$default = $tpl_builder->build($params['default'], $dict);
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = [
					'_action' => 'prompt.radios',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'label' => $label,
					'style' => $style,
					'orientation' => $orientation,
					'options' => $options,
					'default' => $default,
				];
				break;
				
			case 'prompt_text':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$label = $tpl_builder->build($params['label'], $dict);
				@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				@$default = $tpl_builder->build($params['default'], $dict);
				@$mode = $params['mode'];
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = [
					'_action' => 'prompt.text',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'label' => $label,
					'placeholder' => $placeholder,
					'default' => $default,
					'mode' => $mode,
				];
				break;
			
			case 'prompt_submit':
				$actions =& $dict->_actions;
				
				$actions[] = array(
					'_action' => 'prompt.submit',
					'_trigger_id' => $trigger->id,
				);
				
				$dict->__exit = 'suspend';
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
			
			case 'respond_text':
				$actions =& $dict->_actions;
				
				@$format = $params['format'];
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['message'], $dict);
				
				switch($format) {
					case 'html':
						break;
						
					case 'markdown':
						$content = DevblocksPlatform::parseMarkdown($content);
						break;
					
					default:
						$format = '';
						break;
				}
				
				$actions[] = array(
					'_action' => 'respond.text',
					'_trigger_id' => $trigger->id,
					'message' => $content,
					'format' => $format,
				);
				break;
				
			case 'respond_sheet':
				$actions =& $dict->_actions;
				
				// [TODO] Handle errors
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$data_query = $tpl_builder->build($params['data_query'], $dict);
				
				$sheet_yaml = $params['sheet_yaml'];
				
				$actions[] = array(
					'_action' => 'respond.sheet',
					'_trigger_id' => $trigger->id,
					'data_query' => $data_query,
					'sheet_yaml' => $sheet_yaml,
				);
				break;
		}
	}
};