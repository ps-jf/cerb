<article id="portalPage{$page->id}">
	<div class="cerb-portal-wrapper">
		<form action="{devblocks_url}c={$page->uri}{/devblocks_url}" method="POST" class="cerb-portal-form">
		{$page_ext->renderForm($page, $portal)}
		</form>
	</div>
</article>

<script type="text/javascript">
$$.ready(function() {
	var $page = document.querySelector('#portalPage{$page->id}');
	var $form = $page.querySelector('form');
	
	$form.addEventListener('submit', function(e) {
		e.stopPropagation();
		e.preventDefault();
		$form.submit();
		return false;
	});
	
	$form.addEventListener('cerb-form-builder-submit', function(e) {
		e.stopPropagation();
		$form.submit();
	});
	
	$form.addEventListener('cerb-form-builder-reset', function(e) {
		e.stopPropagation();
		$form.submit();
	});
});
</script>