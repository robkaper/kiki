{$page.body|parse}

<footer class="pageFooter"><ul>
{foreach $page.publications as $publication}
  <li><a href="{$publication.url}" class="button"><span class="buttonImg {$publication.service}"></span> {$publication.service}</a></li>
{/foreach}

{if $user.admin}
  <li><a href="javascript:showArticleForm({$page.id});" class="button">Wijzigen</a></li>
{/if}
</ul></footer>

{if $user.admin}
  <pre>// TODO: $article->form( $user, true, 'pages' );</pre>
{/if}

{if $page.likes|count}
  <h3>{"Likes"|i18n}</h3>
  {foreach $page.likes as $connection}
    <div style="float: left; background: #deb; margin: 0 5px 0 0; padding: 5px 0 5px 5px;">
      {include 'parts/user-account-image'}
    </div>
  {/foreach}
  <br class="spacer" />
{/if}
