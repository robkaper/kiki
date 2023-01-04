<head>
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width">
<meta name="description" content="{$description}">

{include 'parts/icmb/geo-location'}
{include 'parts/google/meta-site-verification'}

{foreach $stylesheets as $url}
<link rel="stylesheet" type="text/css" href="{$url}">
{/foreach}

{foreach $scripts as $url}
<script type="text/javascript" src="{$url}"></script>
{/foreach}

<title>{$title|strip}</title>
{include 'parts/google/analytics'}
</head>
