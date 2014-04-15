<div id="kikiFlashbag">

{if $kiki.flashBag.notice|count}
	<ul>
	{foreach $kiki.flashBag.notice as $msg}
		<li class="notice">Notice: {$msg|escape}</li>
	</ul>
	{/foreach}
{/if}

{if $kiki.flashBag.warning|count}
	<ul>
	{foreach $kiki.flashBag.warning as $msg}
		<li class="warning">Warning: {$msg|escape}</li>
	</ul>
	{/foreach}
{/if}

{if $kiki.flashBag.error|count}
	<ul>
	{foreach $kiki.flashBag.error as $msg}
		<li class="error">Error: {$msg|escape}</li>
	</ul>
	{/foreach}
{/if}

</div>
