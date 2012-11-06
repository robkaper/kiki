/**
 * Core Javascript functionality for Kiki.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

/**
 * @var boolean $submitClicked Tracks mousedown event to ensure click event
 * on button triggers before focusout hides it.
 */
var submitClicked = false;

/// Updates each element with class .jsonupdate through JSON.
function jsonUpdate()
{
  var ids = [];
  $('#jsonUpdate').html( boilerplates['jsonLoad'] ).fadeIn();
  $('.jsonupdate').each( function() {
    // TODO: Add a class to manage the opacity so it can be configured in the stylesheet.
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

/**
 * Expands a specific comment form.
 * @param string $id full ID of the comment form outer div element (e.g. commentFormWrapper_1)
 */
function growCommentForm( id )
{
  $('#' + id).removeClass( 'shrunk' );
  $('#' + id + ' img.social').show();
}

/// Shrinks all comment forms on a page.
function shrinkCommentForms()
{
  $('[id^=comments_] [id^=commentFormWrapper_]').addClass( 'shrunk' );
  $('[id^=comments_] [id^=commentFormWrapper_] img.social').hide();
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
  // TODO: perhaps we can use a jsonLoggingIn boilerplate or similar for here and twLogin?
  // TODO: don't we need FB.init here?
  $('#jsonUpdate').html( boilerplates['jsonLoad'] ).fadeIn();
  FB.login( function(response) { onFbResponse(response); }, { perms: '' } );
  return false;
}

function onFbResponse(response)
{
  $('#jsonUpdate').empty().fadeOut();

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

function twLogin()
{
  $('#jsonUpdate').html( boilerplates['jsonLoad'] ).fadeIn();
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

function fileUploadHandler( target, id, uri, html )
{
  $('#jsonUpdate').empty().fadeOut();

  if ( !target || !id )
    return;

  if ( $target = $('#' + target) )
  {
    var re = /^albumForm_/;
    if ( re.test(target) )
    {
      $('.albumSelectImage > .imageList').append(html);
      $target.append(html);
      $('.albumSelectImage > .imageList > .noImages').remove();
    }
    else if ( $target.is("textarea") )
      $target.append( '[attachment]' + uri + '[/attachment]' );
  }
  else
  {
    return;
    var val = $('input[name=' + target + ']').val();
    val += ( ";" + uri );
    $('input[name=' + target + ']').val(val);
  }
}

$( function() { 
  if ( $().cleditor )
  {
    $('textarea.cleditor').cleditor( {
      width: "99%",
      controls: "bold italic underline strikethrough subscript superscript | font size " +
        "style | color highlight removeformat | bullets numbering | outdent " +
        "indent | alignleft center alignright justify | undo redo | " +
        "rule image link unlink | cut copy paste pastetext | print source",
      styles:
        [["Paragraph", "<p>"], ["Header 1", "<h1>"], ["Header 2", "<h2>"],
        ["Header 3", "<h3>"],  ["Header 4","<h4>"],  ["Header 5","<h5>"],
        ["Header 6","<h6>"]],
      useCSS: true,
      docType: '<!DOCTYPE html>'
    } );
  }

  var prettify = false;
  $("pre code").each(function() {
    $(this).parent().addClass('prettyprint');
    prettify = true;
  } );

  if ( prettify ) {
    $.getScript( kikiPrefix + '/scripts/prettify/prettify.js', function() { prettyPrint() } );
  }

  $('input[placeholder], textarea[placeholder]').placeholder();

  $('label a.toggleWysiwyg').live('click', function() {
    var id = $(this).parent().attr('for');
    // $('textarea#' + id ).toggleClass('cleditor').cleditor();
    return false;
  } );


  $('[id^=comments_] [id^=commentFormWrapper_]').live( 'focusin', function() {
  if ( submitClicked )
    submitClicked = false;

    shrinkCommentForms();
    growCommentForm( $(this).attr('id') );
  } );

  $('[id^=comments_] [id^=commentFormWrapper_]').live( 'mousedown', function() {
    submitClicked = true;
  } );
    
  $('[id^=comments_] [id^=commentFormWrapper_]').live( 'focusout', function() {
    if ( !submitClicked )
     shrinkCommentForms();
    else
      submitClicked = false;
  } );

/* TODO: partially rjkcust, not practical needs rewriting to manual Preview button
  $('[id^=articleForm_] textarea').live( 'keyup', function() {
    var text = $(this).val();
    var json = { text: text };
    
    $.post( '/test/preview.php', json, function(data) {
      $('[id^=article_] span.body').html(data.preview);
    }, 'json' );
    return false;
  } );
*/

  $('[id^=articleForm_]').live( 'submit', function() {
    // TODO: re-enable JSON posting when embedded file upload is remove for
    // a storage item selection
    return true;

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
        $('#article_0').attr( 'id', 'article_' + data.articleId );
        $('#articleForm_0').attr( 'id', 'articleForm_' + data.articleId );

        $('#article_' + data.articleId).html( data.article );
        $('#articleForm_' + data.articleId + ' input[name=articleId]').attr( 'value', data.articleId );
        // return;
      }

      $('#jsonUpdate').empty().fadeOut();

      var $submit = $('[id^=articleForm_] button[name=submit]');
      $submit.removeClass('disabled');
    } );

    return false;
    
  } );

  $('[id^=comments_] div[id^=commentFormWrapper_]').live( 'submit', function() {
    $('#' + $(this).attr('id') + ' textarea').attr( 'disabled', 'disabled' ).addClass('disabled');
    // $('#' + $(this).attr('id') + ' :input').css( 'color', '#666' );
    $('#' + $(this).attr('id') + ' button').attr( 'disabled', 'disabled' ).addClass('disabled');
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
      $('div[id^=comments_] button').attr( 'disabled', '' ).removeClass('disabled');
      $('div[id^=comments_] textarea').attr( 'disabled', '' ).removeClass('disabled');;

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

    var span = $(this).parent().find('label[for=' + target + '] span.remaining');

    if ( remaining < 0 )
      $(span).addClass('error').removeClass('warning');
    else if ( remaining <= 10 )
      $(span).addClass('warning').removeClass('error');
    else
      $(span).removeClass('error').removeClass('warning');

    $(span).html( remaining );
  } );

  $('#ajaxFileUpload').live( 'submit', function() {
    $('#jsonUpdate').html( boilerplates['jsonSave'] ).fadeIn();
    return true;
  } );

  $('.xalbum .imgw').live('mouseenter', function() {
    var navarrow = $(this).parent().find('.navarrow');
    $(navarrow).each( function() {
      if ( !$(this).hasClass('disabled') )
        $(this).find('img').fadeIn();
    } );
  } );

  $('.xalbum .imgw').live('mouseleave', function() {
    $(this).parent().find('.navarrow img').fadeOut();
  } );

  $('.album .navarrow').click( function() {
    if ( $(this).hasClass('disabled') )
      return false;

    var album = $(this).parent().parent().parent().attr('id');
    var action = $(this).attr('id');
    var current = $(this).parent().parent().find('>img').attr('id');

    var json = { album: album, action: action, current: current };
    $.getJSON( kikiPrefix + '/json/album.php', json, function(data) { 
    if ( data.id )
      // FIXME: only do for relevant album
      // TODO: update comment box
      $('.album .imgw >img').attr('id',data.id).attr('src',data.url); 
      $('.album span.pictureTitle').html(data.title);

      $('.album #navleft').toggleClass( 'disabled', !data.prev ).find('img').fadeTo( 'normal', data.prev?1:0 );
      $('.album #navright').toggleClass( 'disabled', !data.next ).find('img').fadeTo( 'normal', data.next?1:0 );
    } );

    return false;
  } );

  $('.pictureFormItem').hover( function() {
    $(this).find('.img-overlay').show();
  }, function() {
    $(this).find('.img-overlay').hide();
  } );
        
  $('.pictureFormItem .removePicture').click( function() {
    var pictureIdStr = $(this).parent().parent().attr('id');
    var pictureId = pictureIdStr.split("_")[1];
    var albumIdStr = $(this).parent().parent().parent().attr('id');
    var albumId = albumIdStr.split("_")[1];

    $('#pictureDeleteConfirm').dialog( {
      resizable: false,
      modal: true,
      buttons: {
        "Delete": function() {
          $(this).dialog( "close" );

          var json = { albumId: albumId, pictureId: pictureId };

          $.post( kikiPrefix + '/json/album-remove-picture.php', json, function(data) {
            $('#albumForm_' + data.albumId + ' #pictureFormItem_' + data.pictureId).remove();
          } );
        },
        Cancel: function() {
          $(this).dialog( "close" );
        }
      }
    } );

    return false;
  } );

  $( ".albumForm" ).sortable( {
    update: function(event, ui) {
      var json = { albumId: $(this).attr('id'), sortOrder: $(this).sortable('toArray') };
      $.post( kikiPrefix + '/json/album-sort.php', json, function(data) {
      } );
    }
    , items: '>div'
    , placeholder: 'ui-state-highlight'
    , tolerance: 'pointer'
  } );

  $( ".albumForm" ).disableSelection();
  
} );
