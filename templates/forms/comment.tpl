<? global $user, $objectId; ?>
<div class="comment" style="min-height: 0px;">
<?= Boilerplate::socialImage( $user->serviceName(), $user->name(), $user->picture() ); ?>
<div class="commentTxt">
<?= Form::open( "commentForm_". $objectId, Config::$kikiPrefix. "/json/comment.php", "POST" ).
    Form::hidden( "objectId", $objectId ).
    Form::textarea( "comment", null, null, "Schrijf een reactie..." ).
    Form::button( "submit", "submit", "Plaats reactie" ).
    Form::close();
?>
</div>
</div>
