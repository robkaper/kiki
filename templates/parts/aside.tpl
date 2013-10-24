<div id="sw"><aside>

{if $user.id}
  {foreach $activeConnections as $connection}
    {include 'parts/user-account'}
  {/foreach}

  {foreach $inactiveConnections as $connection}
    {include 'buttons/user-connect'}
  {/foreach}
  <div class="box">
  <a class="button" href="{$kiki.accountService.url}/">{"Your Account"|i18n}</a>
  <a class="button" href="{$kiki.accountService.url}/logout">{"Logout"|i18n}</a>
  </div>
{/if}
{if !$user.id}
  <div id="login" class="box">
  {foreach $inactiveConnections as $connection}
    {include 'buttons/user-login'}
  {/foreach}
 	{include 'buttons/user-login'}
  </div>
{/if}

{if $latestArticles}
  <div class="box">
  <strong>{"Latest articles"|i18n}</strong>
  <ul>
  {foreach $latestArticles as $article}
    <li><a href="{$article.url}">{$article.title|escape}</a></li>
  {/foreach}
  </ul>
  </div>
{/if}

<div class="box">
{include 'parts/google/adsense'}
</div>

{include 'customparts/aside'}

</aside></div>