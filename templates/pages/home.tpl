<body>
{include 'parts/header'}
{include 'parts/nav'}
{include 'parts/aside'}

<div id="cw"><div id="content">

{$content}

<style type="text/css">
#content > div.column {
  width: 200px;
  float: left;
  color: #000;
  background: #deb;
  border-radius: 5px;
  margin: 0.5em 0.5em 0.5em 0;
}
</style>

<!-- <div class="column">
<h2>Rob Kaper, de ICT'er</h2>
</div>

<div class="column">
<h2>Rob Kaper, de festivalganger</h2>
</div>

<div class="column">
<h2>Rob Kaper, de Rotterdammer</h2>
</div> -->

<div class="column">
<ul>
{foreach $socialUpdates as $socialUpdate}
<li>{$socialUpdate.post|trim,escape}, {$socialUpdate.network}, {$socialUpdate.ctime}</li>
{/foreach}
</ul>
</div>

<br style="clear: left;" />

</div></div>

{include 'parts/footer'}

<div id="jsonUpdate"></div>
</body>
