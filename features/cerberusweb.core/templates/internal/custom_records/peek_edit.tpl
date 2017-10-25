{$peek_context = CerberusContexts::CONTEXT_CUSTOM_RECORD}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="custom_record">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.name'|devblocks_translate}:</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.singular'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.plural'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="name_plural" value="{$model->name_plural}" style="width:98%;">
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>Ownable by:</legend>
	
	{$owner_contexts = $model->params.owners.contexts}
	{if !is_array($owner_contexts)}{$owner_contexts = []}{/if}
	
	<div style="margin:5px 5px 5px 10px;">
		<label><input type="checkbox" name="params[owners][contexts][]" value="cerberusweb.contexts.app" {if in_array('cerberusweb.contexts.app', $owner_contexts)}checked="checked"{/if}> Cerb</label>
		<label><input type="checkbox" name="params[owners][contexts][]" value="cerberusweb.contexts.group" {if in_array('cerberusweb.contexts.group', $owner_contexts)}checked="checked"{/if}> {'common.groups'|devblocks_translate|capitalize}</label>
		<label><input type="checkbox" name="params[owners][contexts][]" value="cerberusweb.contexts.role" {if in_array('cerberusweb.contexts.role', $owner_contexts)}checked="checked"{/if}> {'common.roles'|devblocks_translate|capitalize}</label>
		<label><input type="checkbox" name="params[owners][contexts][]" value="cerberusweb.contexts.worker" {if in_array('cerberusweb.contexts.worker', $owner_contexts)}checked="checked"{/if}> {'common.workers'|devblocks_translate|capitalize}</label>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
	
	{$options = $model->params.options}
	{if !is_array($options)}{$options = []}{/if}
	
	<div style="margin:5px 5px 5px 10px;">
		<label><input type="checkbox" name="params[options][]" value="hide_search" {if in_array('hide_search', $options)}checked="checked"{/if}> Hide in search menu</label>
	</div>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this custom record?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.custom_record'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
	});
});
</script>