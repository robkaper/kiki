<article class="summary socialupdate">

	<header>
  	<h2><span><a href="{$update.url}">{$update.title|escape}</a></span></h2>

	</header>

	<blockquote>{$update.body|markup}</blockquote>

	<footer>
  	<span class="author">{$update.author}</span>
  	<time class="relTime" datetime="{$update.ctime|date:c}">{$update.relTime} geleden</time>

	  <a href="{$update.url}">{$update.comments|count} reacties, {$update.likes|count} likes</a>
	</footer>

</article>
