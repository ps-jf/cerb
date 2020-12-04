{$peek_context = CerberusContexts::CONTEXT_RESOURCE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="resource">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap">
                <b>{'common.description'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                <input type="text" name="description" value="{$model->description}" style="width:100%;">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap">
                <b>{'common.type'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                <select name="extension_id">
                    <option value=""></option>
                    {foreach from=$resource_extensions item=resource_extension}
                        <option value="{$resource_extension->id}" {if $model->extension_id==$resource_extension->id}selected="selected"{/if}>{$resource_extension->name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        
        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>
    
    <fieldset data-cerb-resource-config style="margin-top:10px;">
        <div>
            <label>
                <input type="radio" name="is_dynamic" value="0" {if !$model->is_dynamic}checked="checked"{/if}>
                {'common.file'|devblocks_translate|capitalize}
            </label>
            <label>
                <input type="radio" name="is_dynamic" value="1" {if $model->is_dynamic}checked="checked"{/if}>
                {'common.automation'|devblocks_translate|capitalize}
            </label>
        </div>

        <div style="margin:5px 10px 0 0;">
            <div data-cerb-file-static style="display:{if !$model->is_dynamic}block{else}none{/if};">
                <table cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td width="1%" nowrap="nowrap">
                            <b>{'common.upload'|devblocks_translate|capitalize}:</b>
                        </td>
                        <td>
                            <input type="file" name="file" value="">
                        </td>
                    </tr>
                </table>
            </div>

            <div data-cerb-file-dynamic style="display:{if $model->is_dynamic}block{else}none{/if};">
                <fieldset data-cerb-event-resource-get class="peek black">
                    <legend>Event: Get resource (KATA)</legend>
                    <div class="cerb-code-editor-toolbar">
                        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
                            'caller_name' => 'cerb.toolbar.eventHandlers.editor'
                        ])}

                        {$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  #label: Automation
  uri: ai.cerb.eventHandler.automation
  inputs:
    trigger: cerb.trigger.resource.get
"}

                        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

                        {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

                        <div class="cerb-code-editor-toolbar-divider"></div>
                    </div>
                    <textarea name="automation_kata" data-editor-mode="ace/mode/cerb_kata">{$model->automation_kata}</textarea>
                </fieldset>
            </div>
        </div>
    </fieldset>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this resource?
            </div>

            <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
            <button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
        </fieldset>
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
        {/if}
    </div>

</form>

<script type="text/javascript">
    $(function() {
        var $frm = $('#{$form_id}');
        var $popup = genericAjaxPopupFind($frm);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Resource'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            var $file_mode = $popup.find('input[name=is_dynamic]');
            var $file_mode_static = $popup.find('[data-cerb-file-static]');
            var $file_mode_dynamic = $popup.find('[data-cerb-file-dynamic]');
            
            $file_mode.on('click', function(e) {
                e.stopPropagation();
                
                var $target = $(e.target);
                
                if('1' === $target.val()) {
                    $file_mode_static.hide();
                    $file_mode_dynamic.fadeIn();
                } else {
                    $file_mode_dynamic.hide();
                    $file_mode_static.fadeIn();
                }
            });
            
            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Close confirmation

            $popup.on('dialogbeforeclose', function(e, ui) {
                var keycode = e.keyCode || e.which;
                if(keycode === 27)
                    return confirm('{'warning.core.editor.close'|devblocks_translate}');
            });
            
            // Editors

            $popup.find('textarea[data-editor-mode]')
                .cerbCodeEditor()
            ;
            
            var $fieldset_resource_get = $popup.find('[data-cerb-event-resource-get]');

            var doneFunc = function(e) {
                e.stopPropagation();

                var $target = e.trigger;

                if(!$target.is('.cerb-bot-trigger'))
                    return;

                if(!e.eventData || !e.eventData.exit)
                    return;

                if (e.eventData.exit === 'error') {
                    // [TODO] Show error

                } else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
                    var $toolbar = $target.closest('.cerb-code-editor-toolbar');
                    var $automation_editor = $toolbar.nextAll('pre.ace_editor');

                    var automation_editor = ace.edit($automation_editor.attr('id'));
                    automation_editor.insertSnippet(e.eventData.return.snippet);
                }
            };

            $fieldset_resource_get.find('.cerb-code-editor-toolbar')
                .cerbToolbar({
                    caller: {
                        name: 'cerb.toolbar.eventHandlers.editor',
                        params: {
                            trigger: 'cerb.trigger.resource.get',
                            selected_text: ''
                        }
                    },
                    start: function(formData) {
                    },
                    done: doneFunc
                })
            ;

            // [UI] Editor behaviors
            {include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
        });
    });
</script>
