<div id="fb-root"></div>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
FB.init( {appId: '<?= Config::$facebookApp ?>', status: true, cookie: true, xfbml: true} );
FB.Event.subscribe( 'auth.sessionChange', function(response) { onFbResponse(response); } );
</script>