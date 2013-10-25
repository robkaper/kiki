{if $kiki.get.dialog}
	<span id="dialogTitle">{"Login"|i18n}</span>
{/if}

{if $errors|count}
	<p>
		Login failed due to the following errors:</p>

	<ul>
	{foreach $errors as $error}
		<li>{$error}</li>
	{/foreach}
	</ul>
{/if}

{if !$user.id}
		{if $kiki.get.dialog}
			{foreach $inactiveConnections as $connection}
		  	{include 'buttons/user-login'}
			{/foreach}
			{include 'buttons/user-newaccount'}
		{/if}

		{include 'forms/user-login'}
{/if}
