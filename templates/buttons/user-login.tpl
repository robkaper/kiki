{if $connection}
	<a class="button" href="{$connection.loginUrl|escape}" rel="nofollow"><span class="buttonImg {$connection.serviceName}"></span> {"Login with"|i18n} {$connection.serviceName}</a>
{else}
	<a class="button" href="{$kiki.accountService.url}/login" rel="nofollow"><span class="buttonImg Email"></span> {"Login with e-mail"|i18n}</a>
{/if}
