<?

class Google
{
  public static function siteVerification()
  {
    if ( !Config::$googleSiteVerification )
      return null;

    return "<meta name=\"google-site-verification\" content=\"". Config::$googleSiteVerification. "\"/>\n";
  }

  public static function analytics()
  {
    if ( !Config::$googleAnalytics )
      return null;

    ob_start();
?>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '<?= Config::$googleAnalytics ?>']);
_gaq.push(['_trackPageview']);

( function() {
  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
} )();
</script>
<?
    $script = ob_get_contents();
    ob_end_clean();
    return $script;
  }

  public static function adSense( $slot )
  {
    if ( !Config::$googleAdsense )
      return null;

    ob_start();
?>
<div>
<script type="text/javascript"><!--
google_ad_client = "<?= Config::$googleAdsense ?>";
google_ad_slot = "<?= $slot; ?>";
google_ad_width = 120;
google_ad_height = 240;
//-->
</script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>
<?
    $script = ob_get_contents();
    ob_end_clean();
    return $script;
  }

}
  
?>