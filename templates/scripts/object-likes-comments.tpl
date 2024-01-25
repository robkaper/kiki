<script nonce="{{$cspNonce}}">
document.addEventListener( 'DOMContentLoaded', function() {
  $('main').on( 'click', 'button.commentButton', function( clickEvent ) {
    clickEvent.preventDefault();

    if ( $(this).hasClass('disabled') )
      return;

    $(this).addClass('disabled');

    var objectId = $(this).attr('data-object-id');
    var comment = $('textarea#object-' + objectId + '-text').val();

    $.ajax( {
      url: '/kiki/objects/action',
        type: 'post',
        data: {
          json: true,
          objectId: objectId,
          action: 'comment',
          comment: comment
        },
        xhrFields: {
          withCredentials: true
        },
        success: function( response ) {
          if (response) {
            switch( response.action ) {
              case 'comment':
                $('div#object-' + response.object_id + '-comments button').removeClass('disabled');
                $('div#object-' + response.object_id + '-comments textarea').val( '' ).before( response.comment );
                var count = parseInt( $('span#object-' + response.object_id + '-commentCount').html() );
                count = count ? count+=1 : 1;
                $('span#object-' + response.object_id + '-commentCount').html( count );
                break;

              default:;
            }
          }
        },
        error: function( response ) {
       },
      } );
  } );

  $('main').on( 'click', '.actions button.objectButton', function( clickEvent ) {
    clickEvent.preventDefault();

    var objectId = $(this).attr('data-object-id');
    var action = $(this).attr('data-action');

    if ( action == 'comment' )
    {
      var form = $('#object-' + objectId + '-comments');
      if ( form.hasClass('hidden') )
        form.hide().removeClass('hidden');

      form.slideToggle();

      return;
    }

    $.ajax( {
      url: '/kiki/objects/action',
        type: 'post',
        data: {
          json: true,
          objectId: objectId,
          action: action
        },
        xhrFields: {
          withCredentials: true
        },
        success: function( response ) {
          if (response) {
            switch( response.action ) {
              case 'likes':
                if ( response.status )
                  $('button#object-' + response.object_id + '-likes-button').addClass('active');
                else
                  $('button#object-' + response.object_id + '-likes-button').removeClass('active');

                $('span#object-' + response.object_id + '-likes').html( response.likes>0 ? response.likes : null );
                break;

              default:;
            }
          }
        },
        error: function( response ) {
       },
      } );
  } );
} );
</script>
