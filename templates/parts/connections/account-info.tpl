<h2>{$connection.serviceName}</h2>

Jouw account is gekoppeld aan {$connection.serviceName} account <strong>{$connection.screenName}</strong> ({$connection.userName}).

{if $connection.permissions|count}
	<p>Deze website kan gebruik maken van de volgende permissies:</p>
	<ul>
	{foreach $connection.permissions as $permission}
		{if $permission.value}
			<li>{$permission.description|escape}: <strong>ja</strong>{if $permission.revokeUrl} (<a href="{$permission.revokeUrl}">Trek '{$permission.key}' rechten in</a>){/if}</li>
		{/if}
	{/foreach}
	{foreach $connection.permissions as $permission}
		{if !$permission.value}
			<li>{$permission.description|escape}: <strong>nee</strong>{if $permission.grantUrl} (<a href="{$permission.grantUrl}">Voeg '{$permission.key}' rechten toe</a>){/if}</li>
		{/if}
	{/foreach}
	</ul>
{/if}

{if $connection.subAccounts|count}
	<p>De volgende subaccounts zijn actief voor {$connection.serviceName} account <strong>{$connection.screenName}</strong>:</p>
	<ul>
	{foreach $connection.subAccounts as $subAccount}
		<li>{$subAccount.name}</li>
		<!-- <li>{$subAccount|dump}</li> -->
	{/foreach}
	</ul>		
{/if}
