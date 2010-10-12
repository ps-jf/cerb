<html>
<head>
	<meta content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}" http-equiv="content-type">
	<title>{$page_title}</title>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=js/jquery.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=js/jquery.MultiFile.pack.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=js/jquery.validate.pack.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=js/cerb4.common.js{/devblocks_url}"></script>
	
	<style type='text/css'>
		{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/style.css.tpl"}
		{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/user_styles.css.tpl"}
	</style>
</head>

<body>
{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/header.tpl"}

<div style="clear:both;margin-bottom:5px;"></div>

<ul class="menu">
{foreach from=$menu item=item name=menu}
<li {if !empty($module) && 0==strcasecmp($module->manifest->params.uri,$item->manifest->params.uri)}class="selected"{/if}>
	<a href="{devblocks_url}c={$item->manifest->params.uri}{/devblocks_url}">{$item->manifest->params.menu_title|devblocks_translate|lower}</a>
</li>
{/foreach}
{if !empty($active_user)}
	<li style="float:right;"><a href="{devblocks_url}c=login&a=signout{/devblocks_url}">sign out</a></li>
{else}
	<li style="float:right;background-color:rgb(46,183,39);"><a style="color:rgb(255,255,255);" href="{devblocks_url}c=login{/devblocks_url}">sign on</a>
{/if}
</li>
</ul>
<div style="clear:both;border-top:2px solid rgb(8,90,173);">
</div>

<table cellpadding="5" cellspacing="0" border="0" width="100%" align="center">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<!-- Sidebar -->
			{if !empty($module) && method_exists($module,'renderSidebar')}
			{$module->renderSidebar($module_response)}
			{/if}						
		</td>
		
		<td width="99%" valign="top">
			<div id="content">
			{if !empty($module)}
			{$module->writeResponse($module_response)}
			{/if}
			</div>
		</td>
	</tr>
</table>

{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/footer.tpl"}

<div id="tagline" align="right">
	<a href="http://www.cerberusweb.com/" target="_blank"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/_wgm/logo_small.gif{/devblocks_url}" border="0"></a>
</div>

<br>

</body>

</html>
