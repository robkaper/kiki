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

function showArticleForm( articleId )
{
  if ($('#articleForm_' + articleId).is(":hidden"))
    $('#articleForm_' + articleId).show();
  else
    $('#articleForm_' + articleId).hide();
}

function fileUploadHandler( target, id, uri, html )
{
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
  $('input[placeholder], textarea[placeholder]').placeholder();

  $('label a.toggleWysiwyg').live('click', function() {
    var id = $(this).parent().attr('for');
    return false;
  } );


  $('[id^=articleForm_]').live( 'submit', function() {
    // TODO: re-enable JSON posting when embedded file upload is remove for
    // a storage item selection
    return true;

    var $submit = $('#' + $(this).attr('id') + ' button[name=submit]');
    if ( $submit.hasClass('disabled') )
      return false;

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

      var $submit = $('[id^=articleForm_] button[name=submit]');
      $submit.removeClass('disabled');
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

  // Carousel
  var $slider = $('article header .slider'); // class or id of carousel slider
  var $slide = 'img'; // could also use 'img' if you're not using a ul
  var $transition_time = 2000; // 2 seconds
  var $time_between_slides = 3000; // 3 seconds

  function slides(){
    return $slider.find($slide);
  }

  // auto scroll 
  $interval = setInterval(
    function(){
      var $i = $slider.find($slide + '.active').index();

      slides().eq($i).removeClass('active');
      // slides().eq($i).hide();

      if (slides().length == $i + 1) $i = -1; // loop to start

      // slides().eq($i + 1).show();
      slides().eq($i + 1).addClass('active');
    }
    , $transition_time + $time_between_slides 
  );

	$('a.dialog').click( function() {
		console.log( $(this) );
		var url = $(this).attr('href');


		var json = { dialog: 1 };
		$.get( url, json, function(data) {
			$('#kikiDialog').html( data );
			$('#kikiDialog').attr('title', $('#kikiDialog #dialogTitle').html() );
//			$('#kikiDialog').title( "kiki Dialog" );
//			$('#kikiDialog').show();
			console.log(data);


    $('#kikiDialog').dialog( {
      resizable: false,
      modal: true,
      buttons: {
//        "Delete": function() {
//          $(this).dialog( "close" );

//          var json = { albumId: albumId, pictureId: pictureId };

//          $.post( kikiPrefix + '/json/album-remove-picture.php', json, function(data) {
//            $('#albumForm_' + data.albumId + ' #pictureFormItem_' + data.pictureId).remove();
//          } );
//        },

//        Close: function() {
//          $(this).dialog( "close" );
//        }

      }
    } );



		} );
	
		return false;

	} );

} );
