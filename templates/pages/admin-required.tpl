<body>
{include 'parts/header'}
{include 'parts/nav'}
{include 'parts/aside'}

<div id="cw" class="twosides"><div id="content">
  <h1>{$title}</h1>

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
