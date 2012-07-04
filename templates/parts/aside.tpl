<div id="sw"><aside>

{if $user.id}
  {foreach $activeConnections as $connection}
    {include 'parts/user-account'}
  {/foreach}

  {foreach $inactiveConnections as $connection}
    {include 'buttons/user-connect'}
  {/foreach}
  <div class="box">
  <a class="button" href="{$config.kikiPrefix}/account/">{"Your Account"|i18n}</a>
  <a class="button" href="{$config.kikiPrefix}/account/logout.php">{"Logout"|i18n}</a>
  </div>
{/if}
{if !$user.id}
  <div id="login" class="box">
  {foreach $inactiveConnections as $connection}
    {include 'buttons/user-login'}
  {/foreach}
  {*include 'buttons/user-newaccount'*}
  </div>
{/if}

<div class="box">
{* // FIXME: make conditional based on Config::privacyUrl or something similar, even though I think every site should have a proclaimer and privacy policy... }
<p><a href="/proclaimer.php#privacy">Privacybeleid</a></p>
{include 'parts/google/adsense'}
</div>

</aside></div>