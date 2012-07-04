<div id="comments_{$objectId}" class="comments">
{foreach $comments as $comment}
  {if $comment.id}
    {include 'parts/comments-single'}
  {else}
    {include 'parts/comments-dummy'}
  {/if}
{/foreach}
{include 'forms/comment'}
</div>
