<?

class Google
{
  public static function siteVerification()
  {
    if ( !Config::$googleSiteVerification )
      return;

    include Template::file('external/google/meta-site-verification');
  }

  public static function analytics()
  {
    if ( !Config::$googleAnalytics )
      return;

    include Template::file('external/google/analytics');
  }

  public static function adSense( $slot = null )
  {
    if ( !Config::$googleAdsense )
      return;

    include Template::file('external/google/adsense');
  }

}
  
?>