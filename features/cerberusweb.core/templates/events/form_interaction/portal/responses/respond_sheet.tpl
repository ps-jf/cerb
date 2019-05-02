<div class="cerb-portal-form-response-sheet">
	<table cellpadding="0" cellspacing="0" class="cerb-portal-data-table">
		<tr>
			{foreach from=$columns item=column name=columns}
			<th data-column-key="{$column.key}" data-column-type="{$column.type}">{$column.label}</th>
			{/foreach}
		</tr>
	{foreach from=$rows item=row name=rows}
		<tr>
			{foreach from=$columns item=column name=columns}
			<td style="{if $column.style.weight}font-weight:{$column.style.weight};{/if}">{$row[$column.key] nofilter}</td>
			{/foreach}
		</tr>
	{/foreach}
	</table>
</div>