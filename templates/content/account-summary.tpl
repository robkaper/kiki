{foreach $activeConnections as $connection}
 {include 'parts/connections/account-info'}
{/foreach}

{if $user.id}
	<h2>Sociale hulpmiddelen</h2>
	<ul>
		<li><a href="social.php">Update status</a></li>
		<li>Update je status en foto's door ze te e-mailen naar:<br><a href="mailto:$user.emailUploadAddress">{$user.emailUploadAddress}</a></li>
	</ul>
{else}
	<p>Login first.</p>
	{*include 'parts/account/login'*}
{/if}
