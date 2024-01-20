<article id="article_{$article.id}">
  <header>
    <h1>{{$article.title|escape}}</h1>
    <time class="relTime" datetime="{$article.ptime|date:c}" pubdate>{if $article.useRelTime}{{$article.relTime}} ago{else}{{$article.ptime|date:j F Y}}{/if}</time>
    &mdash; <span class="author">{$article.author}</span>
{if $image}
    <br><br><img src="{$image|thumb:800x300.c}" alt="{{$article.title|escape}}" class="rounded" />
{/if}
  </header>

  <div class="body">{$article.body}</div>

  <footer></footer>

</article>
