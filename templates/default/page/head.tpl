<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
<meta name="description" content="<?= $this->description; ?>" />
<? Google::siteVerification(); ?>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= Config::$kikiPrefix ?>/styles/default.css" title="Kiki CMS Default" />
<? foreach( $this->stylesheets as $stylesheet ): ?>
<link rel="stylesheet" type="text/css" href="<?= $stylesheet; ?>" />
<? endforeach; ?>
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<?= Boilerplate::jsonLoad(true); ?>';
boilerplates['jsonSave'] = '<?= Boilerplate::jsonSave(true); ?>';
var fbUser = '<?= $user->fbUser->authenticated ? $user->fbUser->id : 0; ?>';
var twUser = '<?= $user->twUser->authenticated ? $user->twUser->id : 0; ?>';
var kikiPrefix = '<?= Config::$kikiPrefix; ?>';
var requestUri = '<?= $_SERVER['REQUEST_URI']; ?>';
</script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery.placeholder.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/default.js"></script>
<title><?= $title; ?></title>
<? Google::analytics(); ?>
</head>
