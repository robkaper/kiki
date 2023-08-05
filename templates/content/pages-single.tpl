{$page.body|parse}

<footer class="pageFooter"><ul>

{if $user.admin}
  <li><a href="javascript:showArticleForm({$page.id});" class="button">Wijzigen</a></li>
{/if}
</ul></footer>

{if $user.admin}
  {$page.html.editform}
{/if}

{if $page.likes|count}
  <h3>{"Likes"|i18n}</h3>
  {foreach $page.likes as $connection}
    <div style="float: left; background: #deb; margin: 0 5px 0 0; padding: 5px 0 5px 5px;">
      {include 'parts/user-account-image'}
    </div>
  {/foreach}
  <br class="spacer">
{/if}

<br class="clear">
