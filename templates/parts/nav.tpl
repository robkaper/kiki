<nav>
<ul>
{foreach $menu as $menuItem}
<li class="{$menuItem.class}"><a href="{$menuItem.url}">{$menuItem.title}</a></li>
{/foreach}
</ul>
</nav>
<nav class="second">
<ul>
{foreach $subMenu as $subMenuItem}
<li class="{$subMenuItem.class}"><a href="{$subMenuItem.url}">{$subMenuItem.title}</a></li>
{/foreach}
</ul>
</nav>
