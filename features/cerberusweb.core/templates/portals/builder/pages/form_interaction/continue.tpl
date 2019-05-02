{if $continue_options.reset || $continue_options.continue}
<div style="display:flex;margin-top:30px;">
	{if $continue_options.reset}
	<input type="hidden" name="reset" value="0">
	<button style="flex:1 1;max-width:5em;" type="button" class="cerb-button cerb-button-gray cerb-portal-form-builder-reset" tabindex="-1"><span></span></button>
	{/if}
	<div style="flex:2 2;"></div>
	{if $continue_options.continue}
	<button style="flex:1 1;max-width:5em;" type="button" class="cerb-button cerb-portal-form-builder-continue"><span></span></button>
	{/if}
</div>

<script type="text/javascript">
$$.ready(function() {
	var $page = document.querySelector('#portalPage{$page->id}');
	var $form = $page.querySelector('form');
	
	var $button_continue = $page.querySelector('.cerb-portal-form-builder-continue');
	
	if($button_continue) {
		$button_continue.addEventListener('click', function(e) {
			$button_continue.style.display = 'none';
			e.stopPropagation();
			
			var event = $$.createEvent('cerb-form-builder-submit');
			$form.dispatchEvent(event);
		});
	}
	
	var $button_reset = $page.querySelector('.cerb-portal-form-builder-reset');
	var $hidden_reset = $page.querySelector('input[name=reset]');
	
	if($button_reset) {
		$button_reset.addEventListener('click', function(e) {
			$button_reset.style.display = 'none';
			e.stopPropagation();
			
			$hidden_reset.value = '1';
			
			var event = $$.createEvent('cerb-form-builder-reset');
			$form.dispatchEvent(event);
		});
	}
});
</script>
{/if}
