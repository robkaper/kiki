<head>
<meta name="viewport" content="width=device-width">
<meta name="description" content="{$description}">
{include 'parts/icmb/geo-location'}
{include 'parts/google/meta-site-verification'}
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/base/jquery-ui.css" rel="stylesheet" type="text/css">
{if $config.responsive}
  <link rel="stylesheet" type="text/css" href="{$config.kikiPrefix}/styles/responsive.css" title="Kiki CMS Responsive">
{else}
  <link rel="stylesheet" type="text/css" href="{$config.kikiPrefix}/styles/default.css" title="Kiki CMS Default">
{/if}
<link href='http://fonts.googleapis.com/css?family=Droid+Sans' rel='stylesheet' type='text/css'>
{foreach $stylesheets as $url}
<link rel="stylesheet" type="text/css" href="{$url}">
{/foreach}
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$config.kikiPrefix}/scripts/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="{$config.kikiPrefix}/scripts/jquery.placeholder.js"></script>
<script type="text/javascript" src="{$config.kikiPrefix}/scripts/default.js"></script>
{foreach $scripts as $url}
<script type="text/javascript" src="{$url}"></script>
{/foreach}
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<span class="jsonload"><img src="{$config.kikiPrefix}/img/ajax-loader.gif" alt="*"> Laden...</span>';
boilerplates['jsonSave'] = '<span class="jsonload"><img src="{$config.kikiPrefix}/img/ajax-loader.gif" alt="*"> Opslaan...</span>';
var kikiPrefix = '{$config.kikiPrefix}';
var requestUri = '{$server.requestUri}';
</script>
<script type="text/javascript">
<!-- iPad viewport fix -->
(function(doc) {
	var addEvent = 'addEventListener',
	    type = 'gesturestart',
	    qsa = 'querySelectorAll',
	    scales = [1, 1],
	    meta = qsa in doc ? doc[qsa]('meta[name=viewport]') : [];

	function fix() {
		meta.content = 'width=device-width,minimum-scale=' + scales[0] + ',maximum-scale=' + scales[1];
		doc.removeEventListener(type, fix, true);
	}

	if ((meta = meta[meta.length - 1]) && addEvent in doc) {
		fix();
		scales = [.25, 1.6];
		doc[addEvent](type, fix, true);
	}

}(document));
</script>

<title>{$title|strip}</title>
{include 'parts/google/analytics'}
</head>
