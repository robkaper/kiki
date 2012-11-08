<div id="commentFormWrapper_{$objectId}" class="jsonupdate shrunk">
{if $user.id}
  <div class="comment" style="min-height: 0px;">
    <img class="social" style="background-image: url({$activeConnections.0.pictureUrl})" src="/kiki/img/services/None_16.png" alt="[{$activeConnections.0.userName}]">
    <div class="commentTxt">
      <form id="commentForm_{$objectId}" action="{$config.kikiPrefix}/json/comment.php" method="POST">
        <input type="hidden" name="objectId" value="{$objectId}">
        <textarea id="comment" name="comment" placeholder="Schrijf een reactie..."></textarea>
        <p>
          <button name="submit" id="submit" type="submit">Plaats reactie</button>
        </p>
        <br class="spacer">            
      </form>
      <br class="spacer">            
    </div>
  </div>
{else}
  <p>
    <a href="#login">Log in</a> om direct via de site te reageren.
  </p>
{/if}
</div>