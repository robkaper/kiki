<div class="box">
{if $connection}
	<img class="social" style="background-image: url({$connection.pictureUrl})" src="/kiki/img/services/{$connection.serviceName}_16.png" alt="[{$connection.userName}]" title="{$connection.userName}">
	<p>
	<b>{$connection.userName}</b><br>{$connection.serviceName}</p>
	<br class="spacer">
{else}
	<ul>
		<li>User ID: <strong>{$user.id}</strong></li>
		<li>Email: <strong>{$user.email}</strong></li>
	</ul>
{/if}
</div>
