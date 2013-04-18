{if $responsive}
  {include 'parts/header-responsive'}
{else}

<header>
	<a href="/"><img src="{$kiki.config.kikiPrefix}/img/kiki-inverse-74x50.png" alt="{$kiki.config.siteName}" style="width: 74px; height: 50px; float: left;"></a>
	<hgroup>
		<h1><a href="/">{$title}</a></h1>
		<h2>{$subTitle}</h2>
	</hgroup>
	<br class="spacer">
</header>

{/if}
