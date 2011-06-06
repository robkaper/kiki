<div class="comment" id="comment_<?= $objectId; ?>_<?= $id; ?>">
<?= Boilerplate::socialImage( $type, $name, $pic ); ?>
<div class="commentTxt">
<a href="#"><?= $name; ?></a> <?= htmlspecialchars($body); ?>
<br /><time class="relTime"><?= Misc::relativeTime($ctime); ?> geleden</time>
</div>
</div>