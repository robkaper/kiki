<div id="comments_<?= $objectId; ?>" class="comments">
<?= join( "\n", $comments ); ?>
<?= Comments::form( $user, $objectId ); ?>
</div>
