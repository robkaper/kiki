<div class="comment" style="min-height: 0px;">
<img class="social" style="background-image: url({$activeConnections.0.pictureUrl})" src="/kiki/img/komodo/{$activeConnections.0.serviceName}_16.png" alt="[{$activeConnections.0.userName}]" />
<div class="commentTxt">
<?= Form::open( "commentForm_". $objectId, Config::$kikiPrefix. "/json/comment.php", "POST" ).
    Form::hidden( "objectId", $objectId ).
    Form::textarea( "comment", null, null, "Schrijf een reactie..." ).
    Form::button( "submit", "submit", "Plaats reactie" ).
    Form::close();
?>
</div>
</div>
