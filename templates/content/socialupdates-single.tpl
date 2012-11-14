<article class>

	<header>
  	<span class="author">{$update.author}</span>
  	<time class="relTime" datetime="{$update.ctime|date:c}">{$update.relTime} geleden</time>
	</header>

  <blockquote>{$update.body|markup}</blockquote>

  <footer>

  {if $update.likes|count}
    <hr class="clear">
		<h3>{"Likes"|i18n}</h3>
    {foreach $update.likes as $connection}
      <div style="float: left; background: #deb; margin: 0 5px 0 0; padding: 5px 0 5px 5px;">
        {include 'parts/user-account-image'}
      </div>
    {/foreach}
    <br class="spacer">
  {/if}

  <hr class="clear">
  <h3>{"Comments"|i18n}</h3>
  {$update.html.comments}

  {if $update.publications|count}
		<p>Je kunt ook reageren via:</p>
  	<ul>
  		{foreach $update.publications as $publication}
    		<li><a href="{$publication.url}" class="button"><span class="buttonImg {$publication.service}"></span> {$publication.service}</a></li>
	  	{/foreach}
  	<ul>
  {/if}

  </footer>

</article>
