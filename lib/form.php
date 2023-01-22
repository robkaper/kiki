<?php

/**
 * HTML generation for HTML5 forms.
 *
 * @bug Implemented statically, which prevents some sanity checking (such as
 * the right order/use of attachFile, which is confusing currently, even to
 * me).
 * @bug Undocumented pending rewrite away from static.
 * @todo add attachment display to preview/sort/delete attachments
 * @todo require attachment display for all attachments, handle insertion
 * into textarea differently (if at all, inserting BBcode is ugly)
 * @todo All sorts of server- and client-side validation.
 * @todo Use the HTML5 types optimised for numbers, e-mail, etc.
 *
 * @class Form
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Form
{
  public static function open( $id=null, $action=null, $method='POST', $class=null, $enctype=null, $target=null )
  {
    $id = $id ? " id=\"$id\"" : "";
    $class = $class ? " class=\"$class\"" : "";
    $enctype = $enctype ? " enctype=\"$enctype\"" : "";
    $target = $target ? " target=\"$target\"" : "";
    
    return "<form ${id} action=\"$action\" method=\"$method\" ${class}${enctype}${target}>\n";
  }

  public static function close()
  {
    $content = "<br class=\"spacer\">\n";
    $content .= "</form>\n";
    return $content;
  }

  public static function hidden( $id, $value=null )
  {
    return "<input type=\"hidden\" name=\"${id}\" value=\"${value}\">\n";
  }

  public static function text( $id, $value=null, $label=null, $placeholder=null, $password=false )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    $content = "<p class=\"clear\"><label>${label}</label>\n";
    $type = $password ? "password" : "text";
    $content .= "<input type=\"${type}\" name=\"${id}\" value=\"${value}\"${placeholder}></p>\n";
    return $content;
  }

  public static function password( $id, $value=null, $label=null, $placeholder=null )
  {
    return self::text( $id, $value, $label, $placeholder, true );
  }

  public static function textarea( $id, $value=null, $label=null, $placeholder=null, $maxLength=0, $class=null )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    if ( $maxLength )
      $class .= " keyup";
    $maxlength = $maxLength ? " maxlength=\"$maxLength\"" : "";

    if ( $maxLength )
    {
      $remaining = $maxLength - strlen( $value );
      $label .= " <span class=\"remaining\">$remaining</span>\n";
    }

    $template = Template::getInstance();

    // $label .= "<a class=\"button toggleWysiwyg\" href=\"#\">Toggle WYSIWYG</a>";
    $content = "<p class=\"clear\"><label for=\"${id}\">${label}</label>\n";
    $content .= "<textarea id=\"${id}\" name=\"${id}\"${placeholder}${maxlength} class=\"${class}\">${value}</textarea></p>\n";

    return $content;
  }

  public static function checkbox( $id, $checked=false, $label=null, $aside=null )
  {
    $checked = $checked ? " checked" : "";
    $content = "<p class=\"clear\"><label>${label}</label>\n";
    $content .= "<span class=\"checkboxw clear\" xstyle=\"float:left;\"><input type=\"checkbox\" name=\"${id}\" ${checked}><span>${aside}</span></span></p>\n";
    return $content;
  }

  public static function select( $id, &$options=array(), $label=null, $preset=null )
  {
    $content = "<p class=\"clear\"><label>${label}</label>\n";
    $content .= "<select name=\"${id}\">\n";
    $content .= "<option value=\"\">Select ...</option>\n";
    foreach( $options as $id => $label )
    {
      $selected = ($id == $preset) ? " selected" : "";
      $content .= "<option value=\"${id}\"${selected}>${label}</option>\n";
    }
    $content .= "</select></p>\n";
    
    return $content;
  }

  public static function datetime( $id, $value=null, $label=null )
  {
    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"text\" name=\"${id}\" value=\"${value}\" class=\"datetimepicker\"></p>\n";
    ob_start();
?>
<script nonce="<?php echo Config::$cspNonce; ?>">
$( function() {
  $(".datetimepicker").datetimepicker( {
    dateFormat: 'dd-mm-yy',
    changeMonth: false,
    changeYear: false
  } );
} );
</script>
<?php

    $content .= ob_get_contents();
    ob_end_clean();

    return $content;
  }

  public static function button( $id, $type, $label, $style=null )
  {
    $style = $style ? " style=\"$style\"" : "";
    return "<p>\n<button name=\"${id}\" id=\"${id}\" type=\"${type}\"${style}>${label}</button></p>\n";
  }

  public static function file( $id, $label=null, $target = null )
  {
    if ( Misc::isMobileSafari() )
    {
      $label .= "<br><span class=\"small\">Warning: Mobile Safari does not support file uploads.</span>";
      global $user;
      if ( $emailUploadAddress = $user->emailUploadAddress($target) )
        $label .= "<br><span class=\"small\">To upload files to your CMS inbox, e-mail them to:<br><a href=\"mailto:$emailUploadAddress\">$emailUploadAddress</a></span>";
    }

    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"file\" name=\"${id}\"></p>\n";
    return $content;
  }

  public static function albumImage( $id, $label, $albumId, $selected=0 )
  {
    $db = Core::getDb();

    $template = new Template('forms/album-selectimage');

    $imgUrl = $selected ? Storage::url($selected, 75, 75, true) : "/kiki/img/blank.gif";

    $template->assign( 'label', $label );
    $template->assign( 'imgUrl', $imgUrl );

    $images = array();
    $q = $db->buildQuery( "select p.storage_id from pictures p, album_pictures ap where ap.picture_id=p.id AND ap.album_id=%d", $albumId );
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $url = Storage::url($o->storage_id, 75, 75, true);
        $images[] = array( 'storageId' => $o->storage_id, 'url' => $url );
      }
    }
    $template->assign( 'images', $images );
    $template->assign( 'id', $id );
    $template->assign( 'selected', $selected );

    return $template->fetch();
  }

  // TODO: allow to be used inside a form instead of prior/afterwards
  public static function ajaxFileUpload( $label=null, $target=null, $albumId = 0 )
  {
    $content = Form::open( "ajaxFileUpload", Config::$kikiPrefix. "/file-upload.php", 'POST', null, "multipart/form-data", "ajaxFileUploadTarget" );
    $content .= Form::hidden( "target", $target );
    $content .= Form::hidden( "albumId", $albumId );
    $content .= Form::file( "attachment", $label, $albumId ? "album_$albumId" : null );
    $content .= Form::button( "submitAttachment", "submit", "Upload file" );
    $content .= "<iframe id=\"ajaxFileUploadTarget\" name=\"ajaxFileUploadTarget\" src=\"\"></iframe>\n";
    $content .= Form::close();
    return $content;
  }
}

?>
