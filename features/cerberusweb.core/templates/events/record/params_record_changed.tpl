<fieldset style="margin-top:5px;">
	<b>{'common.record.type'|devblocks_translate|capitalize}:</b>
	<br>

	<select name="event_params[context]">
		{foreach from=$contexts item=context}
		<option value="{$context->id}" {if $trigger->event_params.context==$context->id}selected="selected"{/if}>{$context->name}</option>
		{/foreach}
	</select>
</fieldset>