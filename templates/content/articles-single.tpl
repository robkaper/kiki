<article id="article_{$article.id}">
  <header>
    <h1>{$article.title}</h1>
    <time class="relTime" datetime="{$article.ctime|date:c}" pubdate>{$article.relTime} ago</time> &mdash;
    <span class="author">{$article.author}</span>
{if $image}
    <br><br><img src="{$image|thumb:800x300.c}" alt="[{$article.title|escape}]" class="rounded" />
{/if}
  </header>

  <div class="body">{$article.body}</div>

  <footer>

		<hr>

		{if $article.prev.id}
			<span class="prev">« <a href="{$article.prev.url}">{$article.prev.title|escape}</a></span>
		{/if}

		{if $article.next.id}
			<span class="next"><a href="{$article.next.url}">{$article.next.title|escape}</a> »</span>
		{/if}

  	{if $user.admin}
			<hr class="clear">

			<ul>
	    	<li><a href="javascript:showArticleForm({$article.id});" class="button">Wijzigen</a></li>
			</ul>

	    {$article.html.editform}
	  {/if}

  {if $article.likes|count}
    <hr class="clear">
		<h3>{"Likes"|i18n}</h3>
    {foreach $article.likes as $connection}
      <div style="float: left; background: #deb; margin: 0 5px 0 0; padding: 5px 0 5px 5px;">
        {{include 'parts/user-account-image'}}
      </div>
    {/foreach}
    <br class="spacer">
  {/if}

  <hr class="clear">
  <h3>{"Comments"|i18n}</h3>
  {foreach $article.html.comments as $comment}
    {$comment}
  {/foreach}

  </footer>

	{if $article.images|count}
		<hr class="clear">
		{{include 'parts/articles-image-embed'}}
	{/if}

</article>
