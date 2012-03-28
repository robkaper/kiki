<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?= Config::$language; ?>">
<head>
<meta charset="UTF-8"/>
<? /* <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/> */ ?>
<meta name="description" content="<?= $this->description; ?>" />
<? if ( Config::$geoLocation ): ?>
<meta name="ICBM" content="<?= Config::$geoLocation; ?>" />
<meta name="geo.position" content="<?= str_replace( ",", ";", Config::$geoLocation ); ?>" />
<? endif;
   Google::siteVerification(); ?>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= Config::$kikiPrefix ?>/styles/default.css" title="Kiki CMS Default" />
<? foreach( $this->stylesheets as $stylesheet ): ?>
<link rel="stylesheet" type="text/css" href="<?= $stylesheet; ?>" />
<? endforeach; ?>
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<?= Boilerplate::jsonLoad(true); ?>';
boilerplates['jsonSave'] = '<?= Boilerplate::jsonSave(true); ?>';
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
<title><?= strip_tags($title); ?></title>
<? Google::analytics(); ?>
</head>
<?
  include Template::file( $this->bodyTemplate );
?>
</html>
<? Log::debug( "exit: ". $_SERVER['REQUEST_URI'] ); ?>
