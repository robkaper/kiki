<body>
{include 'parts/header'}
{include 'parts/nav'}
{include 'parts/aside'}

<div id="cw"><div id="content">
  <h1>{$title}</h1>
  {$content}
</div></div>

{if $config.facebookApp}
  {* // TODO: consider re-enabling, although Javascript logins are complicated when dealing with multiple connection services
  {*include 'facebook/connect'}
{/if}
{if $config.twitterApp}
  {*if $config.twitterAnywhere}
    {* // TODO: consider re-enabling, although Javascript logins are complicated when dealing with multiple connection services
    {*include 'twitter/anywhere'}
  {*/if}
{/if}

{include 'parts/footer'}

<div id="jsonUpdate"></div>
</body>
