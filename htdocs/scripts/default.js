function jsonUpdate()
{
  var ids = new Array();
  $('#jsonUpdate').html( boilerplates['jsonLoad'] ).fadeIn();
  $('.jsonupdate').each( function() {
    $(this).css( 'opacity', '0.7' );
    ids[ids.length] = $(this).attr('id');
  } );
  if ( ids.length==0 )
    return;

  var json = { 'uri': requestUri, 'content': ids };
  $.getJSON( kikiPrefix + '/json/update.php', json, function(data) {
    $('#jsonUpdate').fadeOut().empty();
    $.each(data.content, function(i,item) {
      $('#' + item.id).html(item.html).css( 'opacity', '1' );
    } );
  } );
}

// HACK: required to queue button.hide() on focusout so .submit() can trigger first
var submitTimer = null;

function growCommentForm( id )
{
  $('#' + id).removeClass( 'shrunk' );
  $('#' + id + ' img.social').show();
}

function shrinkCommentForms()
{
  $('[id^=comments_] [id^=commentForm_]').addClass( 'shrunk' );
  $('[id^=comments_] [id^=commentForm_] img.social').hide();
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
  FB.login( function(response) { onFbResponse(response); }, { perms: '' } );
  return false;
}

function onFbResponse(response)
{
  if (response.session)
  {
    // perms is a comma separated list of granted permissions
    if (response.perms) {}

    FB.api('/me', function(response) {
      if ( response.id == fbUser )
        return;

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

function addAttachment( target, id, uri )
{
  $('#jsonUpdate').empty().fadeOut();
  $('#' + target).append( '[attachment]' + uri + '[/attachment]' );
}

function onReady() {
  $('.album .navarrow img').fadeOut();

  var prettify = false;
  $("pre code").each(function() {
    $(this).parent().addClass('prettyprint');
    prettify = true;
  } );

  if ( prettify ) {
    $.getScript( kikiPrefix + '/scripts/prettify/prettify.js', function() { prettyPrint() } );
  }

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

    var $submit = $('#' + $(this).attr('id') + ' button[name=submit]');
    if ( $submit.hasClass('disabled') )
      return false;

    $('#jsonUpdate').html( boilerplates['jsonSave'] ).fadeIn();
    $submit.addClass('disabled');

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
        $('#articleForm_0').attr( 'id', 'articleForm_' + data.articleId );
        $('#articleForm_' + data.articleId + ' input[name=articleId]').attr( 'value', data.articleId );
        // return;
      }

      $('#jsonUpdate').empty().fadeOut();

      var $submit = $('[id^=articleForm_] button[name=submit]');
      $submit.removeClass('disabled');
    } );

    return false;
    
  } );

  $('[id^=comments_] div[id^=commentForm_]').live( 'submit', function() {
    $('#' + $(this).attr('id') + ' textarea').attr( 'disabled', 'disabled' );
    $('#' + $(this).attr('id') + ' :input').css( 'color', '#666' );
    $('#' + $(this).attr('id') + ' button').hide();
    $('#jsonUpdate').html( boilerplates['jsonSave'] ).fadeIn();

    var last = $('#' + $(this).parent().attr('id') + ' [id^=comment_]:last').attr('id');

    var json = { formId: $(this).attr('id'), json: 1, last: last };

    var $inputs = $('#' + $(this).attr('id') + ' :input');
    $inputs.each( function() {
      json[this.name] = $(this).val();
    } );

    $.post( kikiPrefix + '/json/comment.php', json, function(data) {
      $('#jsonUpdate').empty().fadeOut();

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

  $('textarea.keyup').live( 'keyup', function() {
    var maxLength = $(this).attr('maxlength');
    var length = $(this).val().length;
    var remaining = maxLength - length;
    var target = $(this).attr('id');

    var $(span) = $(this).parent().find('label[for=' + target + '] span.remaining');

    if ( remaining < 0 )
      $(span).addClass('error').removeClass('warning');
    else if ( remaining <= 10 )
      $(span).addClass('warning').removeClass('error');
    else
      $(span).removeClass('error').removeClass('warning');

    $(span).html( remaining );
  } );

  $('#attachFile').live( 'submit', function() {
    $('#jsonUpdate').html( boilerplates['jsonSave'] ).fadeIn();
    return true;
  } );

  $('.album .imgw').live('mouseenter', function() {
    $(this).parent().find('.navarrow img').fadeIn();
  } );

  $('.album .imgw').live('mouseleave', function() {
    $(this).parent().find('.navarrow img').fadeOut();
  } );

  $('.album .navarrow').click( function() {
    var album = $(this).parent().parent().parent().parent().attr('id');
    var action = $(this).attr('id');
    var current = $(this).parent().parent().find('>img').attr('id');

    var json = { album: album, action: action, current: current };
    $.getJSON( kikiPrefix + '/json/album.php', json, function(data) { 
    if ( data.id )
      // FIXME: only do for relevant album
      $('.album .imgw >img').attr('id',data.id).attr('src',data.url); 
    } );

    return false;
  } );
  
}

$( function() { onReady(); } );
