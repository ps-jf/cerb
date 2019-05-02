<div class="cerb-portal-form-response-text">
{if !in_array($format, ['markdown','html'])}
	{$message|escape|nl2br nofilter}
{else}
	{$message nofilter}
{/if}
</div>