<div id="portalWidgetConfig{$model->id}">
	<b>Run this data query:</b> 
	{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
	
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<textarea name="params[data_query]" class="cerb-code-editor" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$model->params.data_query}</textarea>
	</div>
	
	<b>Display these columns:</b> (YAML)
	
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<textarea name="params[sheet_yaml]" class="cerb-code-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$model->params.sheet_yaml}</textarea>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#portalWidgetConfig{$model->id}');
	
	$config.find('textarea.cerb-code-editor')
		.cerbCodeEditor()
		;
});
</script>
