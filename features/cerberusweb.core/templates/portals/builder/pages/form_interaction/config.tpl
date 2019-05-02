<fieldset id="pageConfig{$model->id}" class="peek">
<legend>Use this form builder behavior:</legend>

<button type="button" class="chooser-behavior" data-field-name="params[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="" data-query-required="event:&quot;event.form.interaction.portal&quot;" data-autocomplete="event:&quot;event.form.interaction.portal&quot;" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>

{$behavior = DAO_TriggerEvent::get($model->params.behavior_id)}

<ul class="bubbles chooser-container">
	{if $behavior}
		<li><input type="hidden" name="params[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
	{/if}
</ul>

<div class="parameters">
{if $behavior}
{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" namePrefix="params[behavior_vars]" params=$model->params.behavior_vars macro_params=$behavior->variables}
{/if}
</div>

</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#pageConfig{$model->id}');
	var $bubbles = $fieldset.find('ul.chooser-container');
	var $behavior_params = $fieldset.find('div.parameters');
	
	$fieldset.find('.chooser-behavior')
	.cerbChooserTrigger()
		.on('cerb-chooser-saved', function(e) {
			var $bubble = $bubbles.find('> li:first input:hidden');
			var id = $bubble.first().val();
			
			if(id) {
				genericAjaxGet($behavior_params,'c=internal&a=showBehaviorParams&name_prefix=params[behavior_vars]&trigger_id=' + encodeURIComponent(id));
			} else {
				$behavior_params.html('');
			}
		})
	;
});
</script>