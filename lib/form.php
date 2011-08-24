<?

/**
* @file lib/form.php
* Provides the Form class.
* @class Form
* Builds HTML5 forms.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
* @bug Implemented statically, which prevents some sanity checking (such as
*   the right order/use of attachFile, which is confusing currently, even to
*   me).
* @bug Undocumented pending rewrite away from static.
* @todo add attachment display to preview/sort/delete attachments
* @todo require attachment display for all attachments, handle insertion into textarea differently (if at all, inserting BBcode is ugly)
* @todo All sorts of server- and client-side validation.
* @todo Use the HTML5 types optimised for numbers, e-mail, etc.
*/

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
    $content = "<br class=\"spacer\" />\n";
    $content .= "</form>\n";
    return $content;
  }

  public static function hidden( $id, $value=null )
  {
    return "<input type=\"hidden\" name=\"${id}\" value=\"${value}\" />\n";
  }

  public static function text( $id, $value=null, $label=null, $placeholder=null, $password=false )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    $content = "<p><label>${label}</label>\n";
    $type = $password ? "password" : "text";
    $content .= "<input type=\"${type}\" name=\"${id}\" value=\"${value}\"${placeholder} /></p>\n";
    return $content;
  }

  public static function password( $id, $value=null, $label=null, $placeholder=null )
  {
    return self::text( $id, $value, $label, $placeholder, true );
  }

  // TODO: remove id=, use textarea[name=...] selector instead
  public static function textarea( $id, $value=null, $label=null, $placeholder=null, $maxLength=0 )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    $class = $maxLength ? " class=\"keyup\"" : "";
    $maxlength = $maxLength ? " maxlength=\"$maxLength\"" : "";

    if ( $maxLength )
    {
      $remaining = $maxLength - strlen( $value );
      $label .= " <span class=\"remaining\">$remaining</span>\n";
    }

    if ( $label )
    {
      $content = "<p><label for=\"${id}\">${label}</label>\n";
      $content .= "<textarea id=\"${id}\" name=\"${id}\"${placeholder}${maxlength}${class}>${value}</textarea></p>\n";
    }
    else
      $content = "<textarea id=\"${id}\" name=\"${id}\"${placeholder}>${value}</textarea>\n";

    return $content;
  }

  public static function checkbox( $id, $checked=false, $label=null, $aside=null )
  {
    $checked = $checked ? " checked" : "";
    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"checkbox\" name=\"${id}\" ${checked} /><span>${aside}</span></p>\n";
    return $content;
  }

  public static function select( $id, &$options=array(), $label=null, $preset=null )
  {
    $content = "<p><label>${label}</label>\n";
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
    $content .= "<input type=\"text\" name=\"${id}\" value=\"${value}\" class=\"datetimepicker\" /></p>\n";
    ob_start();
?>
<script>
$( function() {
  $(".datetimepicker").datetimepicker( {
    dateFormat: 'dd-mm-yy',
    changeMonth: false,
    changeYear: false
  } );
} );
</script>
<?
    $content .= ob_get_contents();
    ob_end_clean();

    return $content;
  }

  public static function button( $id, $type, $label, $style=null )
  {
    $style = $style ? " style=\"$style\"" : "";
    return "<p>\n<button name=\"${id}\" id=\"${id}\" type=\"${type}\"${style}>${label}</button></p>\n";
  }

  public static function file( $id, $label=null )
  {
    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"file\" name=\"${id}\" /></p>\n";
    return $content;
  }

  // TODO: allow to be used inside a form instead of prior/afterwards
  public static function attachFile( $label=null, $target=null )
  {
    $content = Form::open( "attachFile", Config::$kikiPrefix. "/add-attachment.php", 'POST', null, "multipart/form-data", "attachTarget" );
    $content .= Form::hidden( "target", $target );
    $content .= Form::file( "attachment", $label );
    $content .= Form::button( "submitAttachment", "submit", "Attach file" );
    $content .= "<iframe id=\"attachTarget\" name=\"attachTarget\" src=\"\"></iframe>\n";
    $content .= Form::close();
    return $content;
  }
}

?>
