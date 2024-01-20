<article id="article_{$article.id}" class="summary">
<div class="grid grid-2 grid-2-onetwo grid-persist">
  <div class="column-center">
{if $image}
    <a href="{{$article.url}}"><img src="{{$image|thumb:200x200.c}}" alt="{{$article.title|escape}}" class="thumb rounded"></a>
{/if}
  </div>
  <div>
    <header>
      <h2><a href="{{$article.url}}">{{$article.title|escape}}</a></h2>
      <time class="relTime" datetime="{$article.ptime|date:c}" pubdate>{if $article.useRelTime}{{$article.relTime}} ago{else}{{$article.ptime|date:j F Y}}{/if}</time>
      &mdash; <span class="author">{$article.author}</span>
    </header>
    <div class="body"><a href="{$article.url}" class="wh">{{$article.summary}}</a></div>
  </div>
</div>
</article>
