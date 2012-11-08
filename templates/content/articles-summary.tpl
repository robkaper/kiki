<article id="article_{$article.id}">

<header>
  {if $article.images.0}
    <img src="{$article.images.0|thumb:160x90.c}" alt="[{$article.title|escape}]" class="thumb">
  {/if}
  <h2><span><a href="{$article.url}">{$article.title|escape}</a></span></h2>

  <span class="author">{$article.author}</span>
  <time class="relTime" datetime="{$article.ctime|date:c}">{$article.relTime} geleden</time>
</header>
  
  <div class="body"><p>{$article.body|summary:2}</p></div>

  <footer>
    <ul>
      <li><a class="xbutton" href="{$article.url}">{"Read more"|i18n}</a></li>
    </ul>
  </footer>

	<br class="spacer">
</article>
