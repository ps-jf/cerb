<div class="cerb-portal-data-sheet">
	{if $rows}
		<table cellpadding="0" cellspacing="0" class="cerb-portal-data-table">
			{if $layout.headings}
			<thead>
				<tr>
					{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{else}
					<th data-column-key="{$column.key}" data-column-type="{$column.type}">{$column.label}</th>
					{/if}
					{/foreach}
				</tr>
			</thead>
			{/if}
			
			{foreach from=$rows item=row name=rows}
			<tbody>
				{if $layout.title_column}
				{$column = $columns[$layout.title_column]}
				<tr>
					<td colspan="{$columns|count-1}" style="padding:0 0 0 5px;font-size:1.1em;font-weight:bold;">{$row[$column.key] nofilter}</td>
				</tr>
				{/if}
				<tr>
				{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{else}
					<td style="{if $column.style.weight}font-weight:{$column.style.weight};{/if}">{$row[$column.key] nofilter}</td>
					{/if}
				{/foreach}
				</tr>
			</tbody>
			{/foreach}
		</table>
	{else}
		No records.
	{/if}
	
	{if $paging}
	<div style="text-align:right;margin-top:5px;">
		{if array_key_exists('first', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.first}">&lt;&lt;</a>{/if}
		{if array_key_exists('prev', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.prev}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
		{if array_key_exists('next', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.next}">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
		{if array_key_exists('last', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.last}">&gt;&gt;</a>{/if}
	</div>
	{/if}
</div>

<script type="text/javascript">
$$.ready(function() {
	var $widget = document.querySelector('#portalWidget{$widget->id}');
	var $dashboard = $widget.closest('.cerb-portal-dashboard');
	
	var $page_links = $widget.querySelectorAll('.cerb-paging');
	
	$$.forEach($page_links, function(index, $link) {
		$link.addEventListener('click', function(e) {
			var $this = this;
			var page = $this.getAttribute('data-page');
			
			if(undefined == page)
				return;
			
			var formData = new FormData();
			formData.append('page', page);
			
			var event = $$.createEvent('cerb-portal-widget-refresh');
			event.widget_id = {$widget->id};
			event.form_data = formData;
			
			$dashboard.dispatchEvent(event);
		})
		;
	});
});
</script>