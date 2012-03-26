<?

/**
 * Utility class for integration of Google services.
 *
 * This class is slightly overkill. Other than checking whether Google
 * features (page verification, Adsense, Analytics) are configured, all it
 * does is included the appropriate templates for HTML/Javascript.
 *
 * @class Google
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Google
{
  /**
   * Includes the meta tag template for Google's site verification, if
   * configured.
   */
  public static function siteVerification()
  {
    if ( !Config::$googleSiteVerification )
      return;

    include Template::file('parts/google/meta-site-verification');
  }

  /**
   * Includes the template for Google Analytics, if configured.
   */
  public static function analytics()
  {
    if ( !Config::$googleAnalytics )
      return;

    include Template::file('parts/google/analytics');
  }

  /**
   * Includes the template for Google Adsense, if configured.
   *
   * @param string $slot Optional ID of an alternative Adsense slot.
   */
  public static function adSense( $slot = null )
  {
    if ( !Config::$googleAdsense )
      return;

    include Template::file('parts/google/adsense');
  }

}
  
?>