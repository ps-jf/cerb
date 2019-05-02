{$element_id = uniqid()}
<div class="cerb-portal-form-prompt cerb-portal-form-prompt-buttons" id="prompt{$element_id}">
	<label>{$label}</label>
	
	<div class="cerb-portal-form-prompt-options">
		{$value = $dict->get($var)}
		<input type="hidden" name="prompts[{$var}]" value="{$value}">
	
		{foreach from=$options item=option}
		<div style="margin:2px 0;{if $orientation == 'horizontal'}display:inline-block;{/if}">
			<button type="button" class="cerb-button cerb-button-gray" value="{$option}" {if $value==$option}style="border:2px solid rgb(39,123,214);"{/if}>{$option}</button>
		</div>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$$.ready(function() {
	var $element = document.querySelector('#prompt{$element_id}');
	var $form = $element.closest('form');
	var $hidden = $element.querySelector('input[type=hidden]');
	var $options = $element.querySelector('.cerb-portal-form-prompt-options');

	$$.forEach($options.querySelectorAll('button'), function(index, $radio) {
		$radio.addEventListener('click', function(e) {
			e.stopPropagation();
			
			var $button = this;
			$hidden.value = $button.value;
			
			// Auto-submit if this is the only prompt
			if(1 == $form.querySelectorAll('.cerb-portal-form-prompt').length) {
				var evt = $$.createEvent('cerb-form-builder-submit');
				$form.dispatchEvent(evt);
				
			} else {
				// [TODO] Use styles
				
				$$.forEach($options.querySelectorAll('button'), function(index, $btn) {
					$btn.style.border = '';
				});
				
				$button.style.border = '2px solid rgb(39,123,214)'; 
			}
			
		});
	});
});
</script>