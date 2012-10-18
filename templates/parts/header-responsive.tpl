<header>
<ul>
<li class="menu" name="mainmenu"><a href="#" class="menu" name="mainmenu"><img src="/test/responsive/list_bullets.png"></a></li>
<li><a href="/"><img src="{$config.kikiPrefix}/img/kiki-inverse-74x50.png" alt="{$config.siteName}" style="xwidth: 74px; height: 32px;"></a></li>
<li class="right">contact</li>
<li class="right"><a href="#" class="menu" name="accountmenu">account</a><li>
</ul>

<section id="title">
<a href="/"><img src="{$config.kikiPrefix}/img/kiki-inverse-74x50.png" alt="{$config.siteName}" style="width: 74px; height: 50px; float: left;"></a>
<span class="title"><a href="/">{$title}</a></span>
<br><span class="subTitle">{$subTitle}</span>
<br class="spacer">
</section>
</header>

<div class="menu" id="mainmenu">
<ul>
{foreach $menu as $menuitem}
  <li><a href="{$menuitem.url}">{$menuitem.title}</a></li>
{/foreach}
</ul>
</div>

<div class="menu right" id="accountmenu">
<ul>
<li>account</li>
<li>logout</li>
<li>fb connect</li>
<li>tw connect</li>
</ul>
</div>

<script type="text/javascript">
$('a.menu').live('mouseenter', function () {
	// showMenu(this.name);
} );

$('a.menu').live('click', function () {
        toggleMenu(this.name);
} );
        
$('div.menu').live('mouseleave', function () {
        // $(this).toggle('fast');
} );

$('div.menu').live('touchleave', function () {
        // $(this).toggle('slow');
} );

function showMenu(id) { toggleMenu(id,true); }
function toggleMenu(id)
{
        $('div.menu').each( function() {
                if ( $(this).attr('id') !== id )
                        $(this).toggle(false);
        } );
        $('div.menu').each( function() {
                console.log( $(this).attr('id') );
                if ( $(this).attr('id') === id )
                {
                        $(this).toggle('fast');
                        return false;
                }
        } );
}
</script>
