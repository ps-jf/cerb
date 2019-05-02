{$element_id = uniqid()}
<div class="cerb-portal-form-prompt cerb-portal-form-prompt-checkboxes" id="{$element_id}">
	<label>{$label}</label>
	
	<div class="cerb-portal-form-prompt-options">
		{$selected_options = $dict->get($var, [])}
	
		{foreach from=$options item=option}
		<label><input type="checkbox" name="prompts[{$var}][]" value="{$option}" {if is_array($selected_options) && in_array($option, $selected_options)}checked="checked"{/if}> {$option}</label>
		{/foreach}
	</div>
</div>