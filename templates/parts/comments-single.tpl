<? echo "[// FIXME: Boilerplate::socialImage]"; return; ?>
<div class="comment" id="comment_<?= $objectId; ?>_<?= $id; ?>">
<?= Boilerplate::socialImage( $type, $name, $pic ); ?>
<div class="commentTxt">
<strong><?= $name; ?></strong> <?= htmlspecialchars($body); ?>
<br /><time class="relTime"><?= Misc::relativeTime($ctime); ?> geleden</time>
</div>
</div>