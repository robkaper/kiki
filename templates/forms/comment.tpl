<? global $user, $objectId; ?>
<? echo "[// FIXME: Boilerplate::socialImage]"; return; ?>
<div class="comment" style="min-height: 0px;">
{$user.activeConnections.0.serviceName}
{$user.activeConnections.0.name}
{$user.activeConnections.0.picture}
<? // Boilerplate::socialImage( $user->serviceName(), $user->name(), $user->picture() ); ?>
<div class="commentTxt">
<?= Form::open( "commentForm_". $objectId, Config::$kikiPrefix. "/json/comment.php", "POST" ).
    Form::hidden( "objectId", $objectId ).
    Form::textarea( "comment", null, null, "Schrijf een reactie..." ).
    Form::button( "submit", "submit", "Plaats reactie" ).
    Form::close();
?>
</div>
</div>
