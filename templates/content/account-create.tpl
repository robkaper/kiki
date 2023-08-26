{if $user.id}
	<p>
		You cannot create a new account when you are already logged in. First log out.</p>
{else}
	{if $accountCreated}
		<p>
			A new account has been created.</p>

		{if $errors|count}
			<ul>
			{foreach $errors as $error}
				<li>{$error}</li>
			{/foreach}
			</ul>
		{else}
			<p>
				To activate your account, use the link in the e-mail just sent to <strong>{$kiki.post.email|escape}</strong>.</p>
		{/if}
	{else}
		{if $errors|count}
			<p>
				Account creation failed due to the following errors:</p>

			<ul>
			{foreach $errors as $error}
				<li>{$error}</li>
			{/foreach}
			</ul>
		{/if}

		{{include 'forms/user-create'}}
	{/if}
{/if}