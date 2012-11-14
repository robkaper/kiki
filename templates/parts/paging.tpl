<hr class="spacer">

<ul class="paging">
	{foreach $pageLinks as $pageLink}
		{if $pageLink.active}
			<li class="active">{$pageLink.title}</li>
		{else}
			<li><a href="page-{$pageLink.id}">{$pageLink.title}</a></li>
		{/if}
	{/foreach}
</ul>