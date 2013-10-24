{if $warnings|count}
	<p>
		Warning:</p>

		<ul>
	{foreach $warnings as $warning}
		<li>{$warning}</li>
	{/foreach}
	</ul>
{/if}

{if $errors|count}
	<p>
		E-mail verification failed due to the following errors:</p>

		<ul>
	{foreach $errors as $error}
		<li>{$error}</li>
	{/foreach}
	</ul>
{else}
	<p>
		Your e-mail address has been verified and you are now logged in.</p>
{/if}
