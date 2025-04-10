<script nonce="{{$cspNonce}}">
document.addEventListener( 'DOMContentLoaded', function() {
{if $kiki.user.id}
  const elMain = document.getElementsByTagName('main').item(0);

  addEventListenerLive( elMain, 'click', async function( event ) {
    event.preventDefault();

    var el = event ? event.target : null;
    while( el && ! (el instanceof HTMLButtonElement) )
       el = el.parentNode;

    if ( !el )
      return false;

    if ( el.classList.contains('disabled') )
      return;
    el.classList.add('disabled');

    const objectId = el.getAttribute('data-object-id');
    const elTextarea = elMain.querySelector( 'textarea#object-' + objectId + '-text' );
    const comment = elTextarea.value;

    const url = '/kiki/objects/action';

    const data = {
      'json': true,
      'objectId': objectId,
      'action': 'comment',
      'comment': comment
    };

    const response = await fetch( url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    } );

    if( response.status != 200 )
      return false;

    const rdata = await response.json();

    switch( rdata.action ) {
      case 'comment':
        el.classList.remove('disabled');

        const template = document.createElement( "template" );
        template.innerHTML = '<div>' + rdata.comment + '</div>';
        const elComment = template.content.children;

        elTextarea.before( elComment[0] );

        const elCount = elMain.querySelector( 'span#object-' + objectId + '-commentCount');
        let count = parseInt( elCount.innerHTML );
        count = count ? count+=1 : 1;
        elCount.innerHTML = count;
        break;

      default:;
    }
  }, 'button.commentButton' );
{/if}

  addEventListenerLive( elMain, 'click', async function( event ) {
    event.preventDefault();

    var el = event ? event.target : null;
    while( el && ! (el instanceof HTMLButtonElement) )
       el = el.parentNode;

    if ( !el )
      return false;

    const objectId = el.getAttribute('data-object-id');
    const action = el.getAttribute('data-action');

    if ( action == 'comment' )
    {
      const elForm = document.getElementById( 'object-' + objectId + '-comments' );
      if ( isVisible(elForm) ) {
        elForm.style.display = 'none';
      } else {
        elForm.classList.remove('hidden');
        elForm.style.display = '';
      }
      return;
    }

{if $kiki.user.id}
    const elIcon = el.querySelector('i');
    elIcon.className = 'fa-solid fa-spinner fa-spin-pulse';

    const url = '/kiki/objects/action';

    const data = {
      'json': true,
      'objectId': objectId,
      'action': action,
    };

    const response = await fetch( url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    } );

    if( response.status != 200 )
      return false;

    const rdata = await response.json();

    switch( rdata.action ) {
      case 'likes':
        elIcon.className = 'fa-solid fa-medal fa-thumb';

        if ( rdata.status )
          el.classList.add('active');
        else
          el.classList.remove('active');

        const elLikes = document.getElementById( 'object-' + objectId + '-likes');
        elLikes.innerHTML = ( rdata.likes>0 ? rdata.likes : null );
        break;

      default:;
    }
{/if}
  }, '.actions button.objectButton' );
} );
</script>
