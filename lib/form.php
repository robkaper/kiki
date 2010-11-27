<?

class Form
{
  public static function open( $id=null, $action=null, $method='POST', $style=null )
  {
    $id = $id ? " id=\"$id\"" : "";
    $style = $style ? " style=\"$style\"" : "";
    return "<form ${id} action=\"$action\" method=\"$method\"${style}>\n";
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

  public static function text( $id, $value=null, $label=null, $placeholder=null )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"text\" name=\"${id}\" value=\"${value}\"${placeholder} /></p>\n";
    return $content;
  }

  public static function textarea( $id, $value=null, $label=null, $placeholder = null )
  {
    $placeholder = $placeholder ? " placeholder=\"$placeholder\"" : "";
    if ( $label )
    {
      $content = "<p><label>${label}</label>\n";
      $content .= "<textarea name=\"${id}\"${placeholder}>${value}</textarea></p>\n";
    }
    else
      $content = "<textarea name=\"${id}\"${placeholder}>${value}</textarea>\n";

    return $content;
  }

  public static function checkbox( $id, $checked=false, $label=null, $aside=null )
  {
    $checked = $checked ? " checked" : "";
    $content = "<p><label>${label}</label>\n";
    $content .= "<input type=\"checkbox\" name=\"${id}\" ${checked}\" /><span>${aside}</span></p>\n";
    return $content;
  }

  public static function select( $id, &$options=array(), $label=null, $preset=null )
  {
    $content = "<p><label>${label}</label>\n";
    $content .= "<select name=\"${id}\">\n";
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
}

?>