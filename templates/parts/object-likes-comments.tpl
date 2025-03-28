<ul class="actions">
  <li><button{if !$kiki.user.id} disabled{/if} id="object-{{$object_id}}-likes-button" class="anchor objectButton{if $likes.active} active{/if}" data-object-id="{{$object_id}}" data-action="likes"><i class="fa-solid fa-award"></i> Likes</button><span id="object-{{$object_id}}-likes" class="smaller">{{$likes.count}}</span></li>
  <li><button id="object-{{$object_id}}-comment-button" class="anchor objectButton" data-object-id="{{$object_id}}" data-action="comment"><i class="fa-solid fa-message"></i> Comment</button><span id="object-{{$object_id}}-commentCount" class="smaller">{if $comments|count}{{$comments|count}}{/if}</span></li>
</ul>
<div id="object-{{$object_id}}-comments"{if !$commentExpand} class="hidden"{/if}>
{foreach $comments as $comment}
  {{$comment}}
{/foreach}
{if $kiki.user.id}
  <form action="" method="POST">
    <textarea id="object-{{$object_id}}-text" class="fw"></textarea>
    <br><div class="buttonBar right"><button class="commentButton larger" data-object-id="{{$object_id}}">Comment</button></div>
    <br class="cb">
  </form>
{else}
    <div class="buttonBar"><a href="/login">Log In</a> to comment</div>
{/if}
</div>
{{include_once 'scripts/object-likes-comments'}}
