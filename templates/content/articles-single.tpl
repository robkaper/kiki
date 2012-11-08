<article id="article_{$article.id}">

  <header>
    {if $article.images.0}
      <img src="{$article.images.0|thumb:780x400.c}" alt="[{$article.title|escape}]">
    {/if}
    <span class="author">{$article.author}</span>
    <time class="relTime" datetime="{$article.ctime|date:c}">{$article.relTime} geleden</time>
  </header>
  
  <div class="body">{$article.body|markup}</div>

  <footer>

		<hr>



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
        {include 'parts/user-account-image'}
      </div>
    {/foreach}
    <br class="spacer">
  {/if}

  <hr class="clear">
  <h3>{"Comments"|i18n}</h3>
  {$article.html.comments}

  {if $article.publications|count}
		<p>Je kunt ook reageren via:</p>
  	<ul>
  		{foreach $article.publications as $publication}
    		<li><a href="{$publication.url}" class="button"><span class="buttonImg {$publication.service}"></span> {$publication.service}</a></li>
	  	{/foreach}
  	<ul>
  {/if}

  </footer>

</article>
