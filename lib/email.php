<?php

/**
 * Constructs an e-mail message to be used by Mailer. Supports a HTML
 * alternative part, MIME attachments as well as RFC-3636 compliant
 * signatures.
 *
 * @url http://tools.ietf.org/html/rfc3676#section-4.3
 *
 * @class Email
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Email
{
  private $subject;
  private $from;
  private $to = [];
  private $cc = [];
  private $bcc = [];
  private $headers;
  private $body;

  private $msgId = null;
  private $mimeBoundary = null;
  private $mimeHeadersCreated = false;

  private $plainMessage = null;
  private $htmlMessage = null;
  private $signature = null;

  private $recipients = array();
  private $headerList = array();
  private $attachments = array();

  /**
   * Initialises the class for use with Mailer::send(). No other action is
   * required (for an empty message), but obviously it is advised to add a
   * body text.
   *
   * @param string $from e-mail address of the sender
   * @param string $to e-mail address of the (primary) recipient
   * @param string $subject subject of the e-mail
   */
  public function __construct( $from, $to, $subject )
  {
    $this->reset();

    $this->setSender( $from );

    if ( is_array($to) ) {
      foreach( $to as $recipient ) {
        $this->addRecipient( $recipient );
      }
    }
    else
      $this->addRecipient( $to );

    $this->subject = $subject;

    $this->setDefaultHeaders();
  }

  /**
   * Resets members.
   *
   * @bug Resets way too little.
   */
  private function reset()
  {
    $this->msgId = "<". sha1(uniqid()). "@". $_SERVER['SERVER_NAME']. ">";
    $this->mimeBoundary = sha1(uniqid());

    $this->headers = null;
    $this->body = null;
  }

  /*
   * Sets members from a database object.
   *
   * @param object Database object.
   */
  public function setFromObject( $o )
  {
  }

  /*
   * Creates a number of basic headers that should be present in all
   * e-mails.
   */
  private function setDefaultHeaders()
  {
    $this->createHeader( 'Return-Path', $this->from );
    $this->createHeader( 'From', $this->from );
    $this->createHeader( 'To', $this->recipients[0] );
    $this->createHeader( 'Subject', $this->subject );
    $this->createHeader( 'Message-ID', $this->msgId );
  }

  /**
   * Creates a header from two name and key entities.
   *
   * @param string $name
   * @param string $value
   */
  public function createHeader( $name, $value )
  {
    $this->addHeader( "$name: $value" );
  }

  /**
   * Adds a header to the internal array.
   *
   * @param string $header Should be in "key: value" format already.
   */
  public function addHeader( $header )
  {
    $this->headerList[] = $header;
  }

  public function setSender( $from )
  {
    $this->from = $from;
  }

  private function resetRecipients()
  {
    $this->recipients = [];
  }

  public function setRecipient( $to )
  {
    $this->resetRecipients();
    $this->addRecipient($to);
  }

  public function addRecipient( $to )
  {
    $this->recipients[] = $to;
  }

  private function resetCc()
  {
    $this->cc = [];
  }

  public function setCc( $to )
  {
    $this->resetCcs();
    $this->addCc($to);
  }

  public function addCc( $to )
  {
    $this->cc[] = $to;
  }

  private function resetBcc()
  {
    $this->bcc = [];
  }

  public function setBcc( $to )
  {
    $this->resetBccs();
    $this->addBcc($to);
  }

  public function addBcc( $to )
  {
    $this->bcc[] = $to;
  }

  public function setPlain( $plain )
  {
    $this->plainMessage = $plain;
  }

  public function setSignature( $signature )
  {
    $this->signature = $signature;
  }

  /**
   * Sets a HTML alternative part.
   *
   * @param string $html the HTML alternative
   * @warning Setting this results in a multipart MIME message with an
   * empty plain text alternative if setPlain() is not called explicitely.
   */
  public function setHtml( $html )
  {
    $this->htmlMessage = $html;
  }

  public function html() { return $this->htmlMessage; }

  public function addFileAttachment( $fileName )
  {
    if ( file_exists($fileName) )
    {
      $data = file_get_contents($fileName);
      $finfo = finfo_open( FILEINFO_MIME );
      $mimeType = finfo_buffer( $finfo, $data );
      finfo_close( $finfo );
      $this->addAttachment( basename($fileName), $data, $mimeType );
    }
  }

  public function addAttachment( $name, $data, $mimeType='application/octet-stream' )
  {
    $this->attachments[] = array( 'name' => $name, 'data' => $data, 'type' => $mimeType );
  }

  public function setHeaders( $headers )
  {
    $this->headers = $headers;
  }

  public function headers()
  {
    if ( $this->headers )
      return $this->headers;

    $this->verifyMimeHeaders();

    return implode( "\n", $this->headerList). "\n";
  }

  private function verifyMimeHeaders()
  {
    if ( !$this->mimeHeadersCreated && ($this->htmlMessage || count($this->attachments)) )
    {
      $this->addHeader( $this->multipartHeader($this->mimeBoundary, $this->htmlMesage ? 'related' : 'mixed', true) );
      $this->mimeHeadersCreated = true;
    }
  }

  public function setBody( $body )
  {
    $this->body = $body;
  }

  public function body()
  {
    if ( $this->body )
      return $this->body;

    if ( $this->htmlMessage )
    {
      $altBoundary = sha1(uniqid());
      return $this->mimePart($this->mimeBoundary, $this->multipartHeader($altBoundary, 'alternative') ).
        $this->textPart($altBoundary).
        $this->htmlPart($altBoundary).
        $this->mimePart($altBoundary).
        $this->attachmentParts().
        $this->mimePart($this->mimeBoundary);
    }
    else if ( count($this->attachments) )
      return $this->textPart($this->mimeBoundary). $this->attachmentParts(). $this->mimePart($this->mimeBoundary);
    else
      return $this->textPart();
  }

  private function multipartHeader( $boundary, $type, $mimeVersion=false )
  {
    return ($mimeVersion ? "MIME-Version: 1.0\n" : "").
      "Content-Type: multipart/$type;\n\tboundary=\"". $this->mimeBoundary($boundary). "\"";
  }

  private function mimeBoundary( $boundary, $asPart=false, $asEnd=false )
  {
    return "----". ($asPart ? "--" : ""). "=_Part_$boundary". ($asEnd ? "--" : "");
  }

  private function mimePart( $boundary, $headers=null, $data=null )
  {
    if ( $headers )
    {
      $part = $this->mimeBoundary( $boundary, true );
      if ( $data )
        return "$part\n$headers\n\n$data\n\n";
      else
        return "$part\n$headers\n\n";
    }
    else
      return $this->mimeBoundary( $boundary, true, true ). "\n\n";
  }

  private function textPart( $mimeBoundary=null )
  {
    $signaturePart = $this->signature ? ("\n-- \n". $this->signature) : null;

    if ( $mimeBoundary )
      return $this->mimePart( $mimeBoundary, "Content-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: quoted-printable", $this->plainMessage. $signaturePart );
    else
      return $this->plainMessage. $signaturePart;
  }

  private function htmlPart( $boundary )
  {
    return $this->mimePart( $boundary, "Content-Type: text/html; charset=utf-8\nContent-Transfer-Encoding: 8bit", $this->htmlMessage );
  }

  private function attachmentParts()
  {
    $attachmentPart = '';

    if( count($this->attachments) )
    {
      foreach( $this->attachments as $attachment )
      {
        $data = $attachment['data'];
        if ( !$data )
          continue;

        $dataStr = chunk_split( base64_encode($data) );
        $cid = basename($attachment['name']). "@". $_SERVER['SERVER_NAME'];
        $attachmentPart .= $this->mimePart( $this->mimeBoundary, "Content-Type: ". $attachment['type']. "; name=". basename($attachment['name']). "\nContent-disposition: attachment\nContent-Transfer-Encoding: base64\nContent-ID: <$cid>", $dataStr );
      }
    }
    return $attachmentPart;
  }

  public function msgId()
  {
    return $this->msgId;
  }

  public function subject()
  {
    return $this->subject;
  }

  public function to()
  {
    // Primary recipient?
    return $this->to = $this->recipients[0];
  }

  public function from()
  {
    return $this->from;
  }

  public function recipients()
  {
    return $this->recipients;
  }

  public function cc()
  {
    return $this->cc;
  }

  public function bcc()
  {
    return $this->bcc;
  }

  public function data()
  {
    return $this->headers(). $this->body();
  }

  /**
   * Sends this e-mail message using the Mailer class, provided for convenience.
   *
   * @param int $priority (optional) queue priority when Config::$mailQueue is enabled
   * @see Mailer::send()
   */
  public function send( $priority = 10 )
  {
    Mailer::send( $this, $priority );
  }
}
