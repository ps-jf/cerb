<div id="pageConfig{$model->id}">
	<fieldset class="peek">
		<legend>{'common.type'|devblocks_translate|capitalize}:</legend>
		
		<select name="params[context]">
			<option value=""></option>
			{foreach from=$contexts item=context}
			<option value="{$context->id}" {if $context->id == $model->params.context}selected="selected"{/if}>{$context->name}</option>
			{/foreach}
		</select>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Page Title:</legend>
		
		<textarea name="params[template_page_title]" placeholder="" style="width:100%;" class="cerb-code-editor" data-editor-mode="ace/mode/twig">{$model->params.template_page_title}</textarea>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Only allow access to records matching this query:</legend>
		
		<textarea name="params[profile_query_records]" placeholder="" style="width:100%;" class="cerb-code-editor" data-editor-mode="ace/mode/twig">{$model->params.profile_query_records}</textarea>
	</fieldset>
	
	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>
		
		{include file="devblocks:cerberusweb.core::portals/builder/pages/_common/config/layout.tpl"}
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#pageConfig{$model->id}');
	
	// Context changed
	
	var $select = $config.find('select[name="params[context]"]');
	
	$select.on('change', function(e) {
		var context = $(this).val();
		
		if(0 == context.length)
			return;
		
		$config.find('textarea[name=template_page_title]')
			.attr('data-context', context)
			;
		
		$config.find('textarea[name=profile_query_records]')
			.attr('data-context', context)
			;
	});
	
	// Query builders
	
	$config.find('.cerb-code-editor')
		.cerbCodeEditor()
		;
});
</script>
