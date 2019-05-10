<div class="cerb-portal-form-response-sheet">
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
</div>