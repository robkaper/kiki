<!DOCTYPE html>
<html lang="en">
<head>
<title>{{$title}}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="/kiki/styles/kiki.css">
</head>
<body>

<header>
  <a href="/"><img src="/kiki/images/kiki-inverse-74x50.png" alt="Kiki" title="Kiki" style="width: 74px; height: 50px;"></a>
</header>

{{include 'parts/flashbag'}}
<main>

{{block 'content'}}
<p>
Welcome to <strong>Kiki framework</strong>!</p>

<p>
This is the default content block from <q>templates/pages/default.tpl</q>. To replace this, extend
<strong>Kiki\Controller</strong> using your own template.</p>
{{/block}}

</main>

</body>
</html>
