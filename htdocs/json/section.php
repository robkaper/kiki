<?
/**
 * Handles creation and modification requests (POSTS) for sections.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  if ($_POST)
  {
    $section = new Section( $_POST['sectionId'] );

    $errors = array();
    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";
    if ( !$user->isAdmin() )
      $errors[] = "Je hebt geen admin rechten.";

    $section->setBaseURI( $_POST['baseURI'] );
    $section->setTitle( $_POST['title'] );
    $section->setType( $_POST['type'] );

    if ( !$section->baseURI() )
      $errors[] = "Je kunt de URL naam niet leeg laten.";
    if ( strstr( $section->baseURI(), "/" ) )
      $errors[] = "Een URL naam mag geen <q>/</q> bevatten.";

    if ( !sizeof($errors) )
    {
      $section->save();
    }
    
    if ( isset($_POST['json']) )
    {
      $response = array();
      $response['sectionId'] = $section->id();
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    if ( !count($errors) )
    {
      Router::redirect( $_SERVER['HTTP_REFERER'], 303 );
      exit();
    }

    $page = new AdminPage();
    $page->header();
    echo "fouten bij opslaan:";
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    $page->footer();
  }
?>