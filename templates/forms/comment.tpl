<div id="commentFormWrapper_22" class="jsonupdate shrunk">
  <div class="comment" style="min-height: 0px;">
    <img class="social" style="background-image: url({$activeConnections.0.pictureUrl})" src="/kiki/img/komodo/{$activeConnections.0.serviceName}_16.png" alt="[{$activeConnections.0.userName}]" />
    <div class="commentTxt">
      <form id="commentForm_{$objectId}" action="{$config.kikiPrefix}/json/comment.php" method="POST">
        <input type="hidden" name="objectId" value="{$objectId}" />
        <textarea id="comment" name="comment" placeholder="Schrijf een reactie..."></textarea>
        <p>
          <button name="submit" id="submit" type="submit">Plaats reactie</button>
        </p>
        <br class="spacer" />            
      </form>
    </div>
  </div>
</div>