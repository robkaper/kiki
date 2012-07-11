<?

/**
 * Boilerplates.
 *
 * Various methods providing boilerplate framework content such as login and account links.
 *
 * @todo Refactor this to use templates and (database) strings that can be
 * updated by JSON or translated by i18n in a generic fashion.  The static
 * methods served a purpose in the earliest revisions of Kiki, but are
 * obviously not suitable as a long term solution.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
*/

class Boilerplate
{
  public static function address()
  {
    return "<address>". nl2br( Config::$address ). "</address>\n";
  }

  public static function copyright()
  {
    return '<a href="/copyright.php">&copy; Copyright</a> '. Config::$copySince. '-'. date("Y"). ' '. Config::$copyOwner. '.';
  }

  static function login()
  {
    return "<p>\n<a href=\"#login\">Log in</a> via Facebook of Twitter om deze content te zien.</p>\n";
  }

  static function commentForm( $objectId )
  {
    $template = new Template( 'forms/comment' );
    $template->assign( 'objectId', $objectId );
    return $template->fetch();
  }

  public static function navMenuItem( &$user, $o )
  {
    if ( $o->admin && !$user->isAdmin() )
      return null;

    $o->icon = false;

    $match = preg_match( "#$o->url#", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null );
    $class = $o->class;
    $class .= ($match ? " active" : null);
    $class .= ($o->icon ? " icon" : null);
    $style = $o->icon ? " style=\"background-image: url(". Config::$iconPrefix. "/$o->icon);\"" : null;

    // return "<li class=\"$class\"${style}><a href=\"$o->url\">$o->title</a></li>\n";

    return array( 'url' => $o->url, 'title' => $o->title, 'style' => $style, 'class' => $class );
  }

  public static function navMenu( &$user, $level = 1 )
  {
    // FIXME: merge non-dynamic entries (such as functionality of /kiki/account/)
    // FIXME: add exact match boolean for url/context checking

    $menu = array();

    $context = null;

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    preg_match( '#(/(.*))/((.*)(\.php)?)#', $requestUri, $matches );
    
    $active = null;
    if ( count($matches) )
    {
      $context = $matches[2];
      $active = $matches[4];
    }
    $paths = explode( "/", $context );

    $db = $GLOBALS['db'];

    $qLevel = $db->escape( $level );
    $qContext = $db->escape( $context );

    if ( $context )
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and ('$qContext' like concat('%', context, '%') or context is null) order by sortorder asc";
    else
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and context is null order by sortorder asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $o = $db->fetchObject($rs) )
        $menu[] = Boilerplate::navMenuItem( $user, $o );

    return $menu;
  }

  public static function facebookPermissions( &$user )
  {
    $content = "";

    foreach( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Twitter' )
      {
        $content .="<h2>Twitter</h2>\n";

        $content .="<ul>\n";
        $content .="<li>Je bent ingelogd als <strong>". $connection->screenName(). "</strong> (". $connection->name(). ") (<a href=\"/proclaimer.php#disconnect\">Ontkoppelhulp</a>).</li>\n";
        $content .="<li>Deze site heeft schrijfrechten. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";
        $content .="<li>Deze site heeft offline toegang. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";

        $content .="</ul>\n";
      }
      else if ( $connection->serviceName() == 'Facebook' )
      {
        $content .= "<h2>Facebook</h2>\n";

        $content .= "<ul>\n";

        $content .= "<li>Je bent ingelogd als <strong>". $connection->name(). "</strong> (<a href=\"/proclaimer.php#disconnect\">Ontkoppelhulp</a>).</li>";

/*
user_about_me			user_activities			user_birthday
user_checkins			user_education_history		user_events
user_games_activity		user_groups			user_hometown
user_interests			user_likes			user_location
user_location_posts		user_notes			user_online_presence
user_photo_video_tags		user_photos			user_questions
user_relationship_details	user_relationships		user_religion_politics
user_status			user_subscriptions		user_videos
user_website			user_work_history

friends_about_me		friends_activities		friends_birthday
friends_checkins		friends_education_history	friends_events
friends_games_activity		friends_groups			friends_hometown
friends_interests		friends_likes			friends_location
friends_location_posts		friends_notes			friends_online_presence
friends_photo_video_tags	friends_photos			friends_questions
friends_relationship_details	friends_relationships		friends_religion_politics
friends_status			friends_subscriptions		friends_videos
friends_website			friends_work_history

ads_management			create_event			create_note
email				export_stream			manage_friendlists
manage_notifications		manage_pages			offline_access
photo_upload			publish_actions			publish_checkins
publish_stream			read_friendlists		read_insights
read_mailbox			read_requests			read_stream
rsvp_event			share_item			sms
status_update			video_upload			xmpp_login
*/

       $actions = array( 'publish_stream' => "schrijfrechten", 'user_events' => "user_events", 'create_event' => "create_event", 'read_stream' => "stream leesrechten" );
        foreach( $actions as $action => $desc )
        {
          $permission = $connection->hasPerm( $action );
          if ( $permission )
          {
            $permissionUrl = Config::$kikiPrefix. "/facebook-revoke.php?id=". $connection->id(). "&permission=$action";
            $content .= "<li>Deze site heeft $desc. (<a href=\"$permissionUrl\">Trek '$action' rechten in</a>).</li>\n";
          }
          else
          {
            $permissionUrl = Config::$kikiPrefix. "/facebook-grant.php?id=". $connection->id(). "&permission=$action";
            $content .= "<li>Deze site heeft geen $desc. (<a href=\"$permissionUrl\">Voeg '$action' rechten toe</a>).</li>\n";
          }
        }

        $content .= "</ul>\n";

      }
    }

    return $content;
  }

}

?>