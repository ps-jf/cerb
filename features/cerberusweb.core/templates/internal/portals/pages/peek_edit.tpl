{$peek_context = 'cerb.contexts.portal.page'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="portal_page">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
	{if $portal}
	<input type="hidden" name="portal_id" value="{$portal->id}">
	{else}
	<div style="margin-bottom:5px;">
		<b>{'common.portal'|devblocks_translate|capitalize}:</b>
		<button type="button" class="chooser-abstract" data-field-name="portal_id" data-context="{CerberusContexts::CONTEXT_PORTAL}" data-single="true" data-query-required="type:&quot;cerb.portal.builder&quot;"><span class="glyphicons glyphicons-search"></span></button>
		
		<ul class="bubbles chooser-container">
			{if $portal}
				<li><input type="hidden" name="portal_id" value="{$portal->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_PORTAL}" data-context-id="{$portal->id}">{$portal->name}</a></li>
			{/if}
		</ul>
	</div>
	{/if}
{/if}

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>

	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.uri'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="uri" value="{$model->uri}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.menu'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="pos" value="{$model->pos|default:50}" maxlength="2" style="width:3em;">
			<small>
			(1 = {'common.first'|devblocks_translate|lower},
			99 = {'common.last'|devblocks_translate|lower},
			0 = {'common.hidden'|devblocks_translate|lower})
			</small>
		</td>
	</tr>
	
	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.visibility'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<label><input type="radio" name="is_private" value="0" {if !$model->is_private}checked="checked"{/if}> {'common.public'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="is_private" value="1" {if $model->is_private}checked="checked"{/if}> Identity required</label>
		</td>
	</tr>
	
	<tbody class="cerb-page-extension" style="{if !$page_extensions}display:none;{/if}">
		<tr>
			<td width="1%" valign="top" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%" valign="top">
				{if $model->id}
					{$page_extension = $model->getExtension()}
					{$page_extension->manifest->name}
				{else}
					<select name="extension_id">
						<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
						{foreach from=$page_extensions item=page_extension}
						<option value="{$page_extension->id}">{$page_extension->name}</option>
						{/foreach}
					</select>
				{/if}
			</td>
		</tr>
	</tbody>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{* The rest of config comes from the extension *}
<div class="cerb-extension-params">
{if $model->id}
	{$page_extension = $model->getExtension()}
	{if $page_extension && method_exists($page_extension,'renderConfig')}
		{$page_extension->renderConfig($model)}
	{/if}
{/if}
</div>

<div class="cerb-placeholder-menu" style="display:none;">
{include file="devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/toolbar.tpl"}
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this portal page?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Portal Page'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Toolbar
		var $toolbar = $popup.find('.cerb-placeholder-menu').detach();
		var $params = $popup.find('.cerb-extension-params');
		var $select_extension = $popup.find('select[name="extension_id"]');
		var $tbody_widget_extension = $popup.find('tbody.cerb-page-extension');
		
		// Abstract choosers
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $target = $(e.target);
				
				if($target.attr('data-field-name') == 'portal_id') {
				}
			})
			;
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Switching extension params
		var $select = $popup.find('select[name=extension_id]');
		
		$select.on('change', function(e) {
			var extension_id = $select.val();
			
			$toolbar.detach();
			
			if(0 == extension_id.length) {
				$params.hide().empty();
				return;
			}
			
			// Fetch via Ajax
			genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=portal_page&action=renderPageConfig&extension=' + encodeURIComponent(extension_id), function(html) {
				$params.find('button.chooser-abstract').cerbChooserTrigger();
				$params.find('.cerb-peek-trigger').cerbPeekTrigger();
				$params.fadeIn();
			});
		});
		
		// Placeholder toolbar
		$popup.delegate(':text.placeholders, textarea.placeholders, pre.placeholders', 'focus', function(e) {
			e.stopPropagation();
			
			var $target = $(e.target);
			var $parent = $target.closest('.ace_editor');
			
			if(0 != $parent.length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				$toolbar.show().insertAfter($parent);
				$toolbar.data('src', $parent);
				
			} else {
				if(0 == $target.nextAll($toolbar).length) {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter($target);
					$toolbar.data('src', $target);
					
					// If a markItUp editor, move to parent
					if($target.is('.markItUpEditor')) {
						$target = $target.closest('.markItUp').parent();
						$toolbar.find('button.tester').hide();
						
					} else {
						$toolbar.find('button.tester').show();
					}
				}
			}
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
