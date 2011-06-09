<?

/**
* @class Google
* Utility class to load Google HTML/Javascript templates. Slightly overkill, but checks whether Google features (page verification, Adsense, Analytics) have been configured.
* @author Rob Kaper <http://robkaper.nl/>
*/

class Google
{
  /**
  * Includes the meta tag template for Google's site verification, if configured.
  */
  public static function siteVerification()
  {
    if ( !Config::$googleSiteVerification )
      return;

    include Template::file('external/google/meta-site-verification');
  }

  /**
  * Includes the template for Google Analytics, if configured.
  */
  public static function analytics()
  {
    if ( !Config::$googleAnalytics )
      return;

    include Template::file('external/google/analytics');
  }

  /**
  * Includes the template for Google Adsense, if configured.
  * @param $slot [string] (optional) ID of an alternative Adsense slot.
  */
  public static function adSense( $slot = null )
  {
    if ( !Config::$googleAdsense )
      return;

    include Template::file('external/google/adsense');
  }

}
  
?>