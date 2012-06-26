<script src="http://platform.twitter.com/anywhere.js?id={$config.twitterApp}&v=1" type="text/javascript"></script>
<script>
twttr.anywhere( function (T) {
  T.bind("authComplete", function (e, user) { onTwLogin(e, user); } );
  T.bind("signOut", function (e) { onTwLogout(e); } );
  // TODO: twttr.anywhere.signOut();

  var twLogin = document.getElementById("twLogin");
  if ( twLogin )
  {
    twLogin.onclick = function () {
      T.signIn();
      return false;
    }
  }
} );
</script>