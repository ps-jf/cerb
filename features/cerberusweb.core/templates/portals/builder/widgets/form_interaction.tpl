<div class="cerb-portal-widget-form-builder">
	<form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-portal-form" onsubmit="return false;">
	{$widget_ext->renderForm($widget, $dict, $is_refresh)}
	</form>
</div>

<script type="text/javascript">
$$.ready(function() {
	var $widget = document.querySelector('#portalWidget{$widget->id}');
	var $form = $widget.querySelector('form');
	
	$form.addEventListener('cerb-form-builder-submit', function(e) {
		e.stopPropagation();
		
		var $dashboard = $form.closest('.cerb-portal-dashboard');
		var formData = new FormData($form);
		
		var event = $$.createEvent('cerb-portal-widget-refresh');
		event.widget_id = {$widget->id};
		event.form_data = formData;
		
		$dashboard.dispatchEvent(event);
	});
	
	$form.addEventListener('cerb-form-builder-reset', function(e) {
		e.stopPropagation();
		
		var $dashboard = $form.closest('.cerb-portal-dashboard');
		
		var formData = new FormData();
		formData.append('reset', '1');
		
		var event = $$.createEvent('cerb-portal-widget-refresh');
		event.widget_id = {$widget->id};
		event.form_data = formData;
		
		$dashboard.dispatchEvent(event);
	});
});
</script>