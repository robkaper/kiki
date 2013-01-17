<div class="comment" id="comment_{$comment.objectId}_{$comment.id}">
<img class="social" style="background-image: url({$comment.pic})" src="/kiki/img/services/{$comment.type}_16.png" alt="[{$comment.name|escape}]">
<div class="commentTxt">
<strong>{$comment.name|escape}</strong> {$comment.body|escape}
<br><time class="relTime" datetime="{$comment.dateTime}">{$comment.relTime} geleden</time>
</div>
</div>