<body>
{include 'parts/header'}
{include 'parts/nav'}
{include 'parts/aside'}

<div id="cw"><div id="content">

<p>
{if $user.id}
You do not have administration privileges.
{else}
Login first.
{/if}
</p>

</div></div>

{include 'parts/footer'}

<div id="jsonUpdate"></div>
</body>
