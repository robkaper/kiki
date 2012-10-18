<head>
<meta charset="UTF-8">
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
{foreach $stylesheets as $url}
<link rel="stylesheet" type="text/css" href="{$url}">
{/foreach}
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<span class="jsonload"><img src="{$config.kikiPrefix}/img/ajax-loader.gif" alt="*"> Laden...</span>';
boilerplates['jsonSave'] = '<span class="jsonload"><img src="{$config.kikiPrefix}/img/ajax-loader.gif" alt="*"> Opslaan...</span>';
var kikiPrefix = '{$config.kikiPrefix}';
var requestUri = '{$server.requestUri}';
</script>
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
<title>{$title|strip}</title>
{include 'parts/google/analytics'}
</head>
