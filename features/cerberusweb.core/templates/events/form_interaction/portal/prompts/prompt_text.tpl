{$element_id = uniqid()}
<div class="cerb-portal-form-prompt cerb-portal-form-prompt-text" id="{$element_id}">
	<label>{$label}</label>
	
	{$value = $dict->get($var)}
	
	{if $mode == 'multiple'}
	<textarea name="prompts[{$var}]" placeholder="{$placeholder}" autocomplete="off">{$value|default:$default}</textarea>
	{else}
	<input name="prompts[{$var}]" type="text" placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
	{/if}
</div>