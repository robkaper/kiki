<div id="comments_{$objectId}" class="comments">
{foreach $comments as $comment}
  {include 'parts/comments-single'}
{/foreach}
{include 'forms/comment'}
</div>
