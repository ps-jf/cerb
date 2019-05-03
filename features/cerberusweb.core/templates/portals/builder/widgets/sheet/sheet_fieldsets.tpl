<div class="cerb-portal-data-sheet">
	<table width="100%" cellspacing="0" cellpadding="0" border="0" class="cerb-portal-data-sheet--fieldset">
	{foreach from=$rows item=row name=rows}
		<tbody>
		{foreach from=$columns item=column name=columns}
		{$value = $row[$column.key]}
		{if $value}
			<tr class="cerb-portal-data-sheet--field">
				<td>
					<label>{$column.label}:</label>
				</td>
				<td>
					{$value nofilter}
				</td>
			</tr>
		{/if}
		{/foreach}
		</tbody>
	{/foreach}
	</table>
</div>