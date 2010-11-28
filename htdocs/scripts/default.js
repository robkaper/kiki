function jsonUpdate()
{
  var ids = new Array();
  $('.jsonupdate').each( function() {
    $(this).prepend( boilerplates['jsonLoad'] );
    ids[ids.length] = $(this).attr('id');
  } );
  if ( ids.length==0 )
    return;

  var json = { 'content': ids };
  $.getJSON( kikiPrefix + '/json/update.php', json, function(data) {
    $('.jsonload').remove();
    $.each(data.content, function(i,item) {
      $('#' + item.id).html(item.html);
    } );
  } );
}

// HACK: required to queue button.hide() on focusout so .submit() can trigger first
var submitTimer = null;

function growCommentForm( id )
{
  $('#' + id + ' textarea').css( 'height', '10em' );
  // TODO: replace all this mojo with a css selector class on form and addClass/removeClass
  $('#' + id + ' :input').css( 'color', '#000' );
  $('#' + id + ' img.social').show();
  $('#' + id + ' button').show();
}

function shrinkCommentForms()
{
  $('[id^=comments_] textarea').css( 'height', '1.5em' );
  $('[id^=comments_] :input').css( 'color', '#666' );
  $('[id^=comments_] [id^=commentForm_] img.social').hide();
  $('[id^=comments_] button').hide();
}

function showArticleForm( articleId )
{
  if ($('#articleForm_' + articleId).is(":hidden"))
    $('#articleForm_' + articleId).show();
  else
    $('#articleForm_' + articleId).hide();
}

function showArticleComments( articleId )
{
  if ($('#comments_' + articleId).is(":hidden"))
    $('#comments_' + articleId).show();
  else
    $('#comments_' + articleId).hide();
}

function fbLogin()
{
  FB.login( function(response) { onFbResponse(response); }, { perms: 'publish_stream' } );
  return false;
}

function onFbResponse(response)
{
  if (response.session)
  {
    // perms is a comma separated list of granted permissions
    if (response.perms) {}

    FB.api('/me', function(response) {
      fbUser = response.id;
      var userName = response.name;

      $('.fbName').text( userName );
      $('img.fbImg').attr( 'alt', userName );
      var fbImg = "url('http://graph.facebook.com/" + fbUser + "/picture')";
      $('img.fbImg').css( 'background-image', fbImg );
      $('#fbYouAre').show();
      $('#fbLogin').hide();
      $('.youUnknown').hide();

      jsonUpdate();
    } );
  }
  else
  {
    fbUser = 0;

    $('.fbName').text( '' );
    $('img.fbImg').attr( 'alt', "" );
    $('img.fbImg').css( 'background-image', "" );
    $('#fbYouAre').hide();
    $('#fbLogin').show();

    if ( !twUser )
      $('.youUnknown').show();
  }
}

function onTwLogin( e, user )
{
  twUser = user.id;

  $('.twName').text( user.name );
  $('img.twImg').attr( 'alt', user.name );
  $('img.twImg').css( 'background-image', "url('" + user.profileImageUrl + "')" );
  $('#twYouAre').show();
  $('#twLogin').hide();
  $('.youUnknown').hide();

  jsonUpdate();
}

function onTwLogout( user )
{
    twUser = 0;

    $('.twName').text( '' );
    $('img.twImg').attr( 'alt', "" );
    $('img.twImg').css( 'background-image', "" );
    $('#twYouAre').hide();
    $('#twLogin').show();

    if ( !fbUser )
      $('.youUnknown').show();
}

function onReady() {
  $('input[placeholder], textarea[placeholder]').placeholder();
           
  $('[id^=comments_] [id^=commentForm_]').live( 'focusin', function() {
  if ( submitTimer )
    clearTimeout( submitTimer );

    shrinkCommentForms();
    growCommentForm( $(this).attr('id') );
  } );

  $('[id^=comments_] [id^=commentForm_]').live( 'focusout', function() {
    // HACK: queue hide() so .submit() can trigger
    submitTimer = setTimeout( function() {
      shrinkCommentForms()
    }, 10 );
  } );

  $('[id^=articleForm_]').live( 'submit', function() {
    $('#' + $(this).attr('id') + ' :input').css( 'color', '#666' );
    $('#' + $(this).attr('id') + ' textarea').attr( 'disabled', 'disabled' );
    $('#' + $(this).attr('id') + ' button').after( boilerplates['jsonSave'] );
    $('#' + $(this).attr('id') + ' button').hide();

    var json = { formId: $(this).attr('id'), json: 1 };
    var $inputs = $('#' + $(this).attr('id') + ' :input');
    $inputs.each( function() {
      var val = $(this).val();
      if ( $(this).is(':checkbox') )
        val = $(this).is(':checked') ? "on" : "";
      json[this.name] = val;
    } );

    // console.log($(this).serialize());

    $.post( kikiPrefix + '/json/article.php', json, function(data) {

      if ( data.articleId )
      {
        $('#article_' + data.articleId).html( data.article );
        // return;
      }

      $('.jsonload').remove();
      $('[id^=articleForm_] :input').css( 'color', '#000' )
      $('[id^=articleForm_] textarea').attr( 'disabled', '' );
      $('[id^=articleForm_] button').attr( 'disabled', '' );
      $('[id^=articleForm_] button').show();

    } );

    return false;
    
  } );

  $('[id^=comments_] div[id^=commentForm_]').live( 'submit', function() {
    $('#' + $(this).attr('id') + ' textarea').attr( 'disabled', 'disabled' );
    $('#' + $(this).attr('id') + ' :input').css( 'color', '#666' );
    $('#' + $(this).attr('id') + ' button').after( boilerplates['jsonSave'] );
    $('#' + $(this).attr('id') + ' button').hide();

    var last = $('#' + $(this).parent().attr('id') + ' [id^=comment_]:last').attr('id');

    var json = { formId: $(this).attr('id'), json: 1, last: last };

    var $inputs = $('#' + $(this).attr('id') + ' :input');
    $inputs.each( function() {
      json[this.name] = $(this).val();
    } );

    $.post( kikiPrefix + '/json/comment.php', json, function(data) {
      $('.jsonload').remove();

      $.each(data.comments, function(i,item) {
        $('[id^=comment_' + data.objectId + '_]:last').after( item );
      } );

      $('div[id^=comments_] #comment').val( '' );
      $('div[id^=comments_] button').attr( 'disabled', '' );
      $('div[id^=comments_] textarea').attr( 'disabled', '' );

      shrinkCommentForms();

      if ( data.comments.length )
        $('.dummy[id^=comment_' + data.objectId + '_]').remove();
    } );

    return false;
  } );
}

$( function() { onReady(); } );
