{if $config.facebookApp}
  {* // TODO: consider re-enabling, although Javascript logins are complicated when dealing with multiple connection services
  {* include 'facebook/connect'}
{/if}

{if $config.twitterApp}
  {*if $config.twitterAnywhere}
    {* // TODO: consider re-enabling, although Javascript logins are complicated when dealing with multiple connection services
      {* include 'twitter/anywhere'}
  {* /if}
{/if}
                
<div id="fw">
  <div class="footer">{$footerText}</div>
</div>