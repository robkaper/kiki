<div class="comment" id="comment_{$comment.objectId}_{$comment.id}>">
<img class="social" style="background-image: url({$comment.pic})" src="/kiki/img/komodo/{$comment.type}_16.png" alt="[{$comment.name}]" />
<div class="commentTxt">
<strong>{$comment.name|escape}</strong> {$comment.body|escape}
<br /><time class="relTime">{$comment.relTime} geleden</time>
</div>
</div>