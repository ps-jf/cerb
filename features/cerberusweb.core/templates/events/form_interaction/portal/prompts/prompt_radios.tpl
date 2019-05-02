{$element_id = uniqid()}
<div class="cerb-portal-form-prompt cerb-portal-form-prompt-radios" id="prompt{$element_id}">
	<label>{$label}</label>
	
	<div class="cerb-portal-form-prompt-options">
		{$value = $dict->get($var)}
	
		{foreach from=$options item=option}
		<div style="{if $orientation == 'horizontal'}display:inline-block;{/if}">
			<label><input type="radio" name="prompts[{$var}]" value="{$option}" {if $value|default:$default==$option}checked="checked"{/if}> {$option}</label>
		</div>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$$.ready(function() {
	var $element = document.querySelector('#prompt{$element_id}');
	var $form = $element.closest('form');
	var $options = $element.querySelector('.cerb-portal-form-prompt-options');

	$$.forEach($options.querySelectorAll('input[type=radio]'), function(index, $radio) {
		$radio.addEventListener('click', function(e) {
			e.stopPropagation();
			
			// Auto-submit if this is the only prompt
			if(1 == $form.querySelectorAll('.cerb-portal-form-prompt').length) {
				var evt = $$.createEvent('cerb-form-builder-submit');
				$form.dispatchEvent(evt);
			}
		});
	});
});
</script>