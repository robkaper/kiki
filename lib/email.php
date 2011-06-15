<?

/**
* @class Email
* Constructs an e-mail message to be used by Mailer. Supports a HTML alternative part,
* MIME attachments as well as RFC-3636 compliant signatures.
* @see http://tools.ietf.org/html/rfc3676#section-4.3
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Email
{
  private $subject;
  private $from;
  private $to;
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
  * required (for an empty message), but obviously it is advised to add
  * least set a body text with setPlain().
  * @param $from [string] e-mail address of the sender
  * @param $to [string] e-mail address of the (primary) recipient
  * @param $subject [string] subject of the e-mail
  * @bug No Cc: or Bcc: support, untested multiple To: support (addRecipient).
  * @bug Document class.
  * @bug reset() resets way too little.
  */
  public function __construct( $from, $to, $subject )
  {
    $this->reset();

    $this->setSender( $from );
    $this->addRecipient( $to );

    $this->subject = $subject;

    $this->setDefaultHeaders();
  }

  private function reset()
  {
    $this->msgId = "<". sha1(uniqid()). "@". $_SERVER['SERVER_NAME']. ">";
    $this->mimeBoundary = sha1(uniqid());

    $this->headers = null;
    $this->body = null;
  }

  private function setDefaultHeaders()
  {
    $this->createHeader( 'Return-Path', $this->from );
    $this->createHeader( 'From', $this->from );
    $this->createHeader( 'To', $this->recipients[0] );
    $this->createHeader( 'Subject', $this->subject );
    $this->createHeader( 'Message-ID', $this->msgId );
  }

  public function createHeader( $name, $value )
  {
    $this->addHeader( "$name: $value" );
  }

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
    $this->recipients = array();
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

  public function setPlain( $plain )
  {
    $this->plainMessage = $plain;
  }

  public function setSignature( $signature )
  {
    $this->signature = $signature;
  }

  /// Sets a HTML alternative part.
  /// @param $html [string] the HTML alternative
  /// @warning Setting this results in a multipart MIME message with an
  ///   empty plain text alternative if setPlain() is not called explicitely.
  public function setHtml( $html )
  {
    $this->htmlMessage = $html;
  }

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

/// @deprecated
//    $headers = array();
//    foreach( $this->headerList as $name => $value )
//      $headers[]= "$name: $value";

    return implode( "\n", $this->headerList). "\n";
  }

  private function verifyMimeHeaders()
  {
    if ( !$this->mimeHeadersCreated && ($this->htmlMessage || count($this->attachments)) )
    {
      $this->addHeader( $this->multipartHeader($this->mimeBoundary, 'mixed', true) );
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
        $this->AttachmentParts().
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
        $attachmentPart .= $this->mimePart( $this->mimeBoundary, "Content-Type: ". $attachment['type']. "; name=". basename($attachment['name']). "\nContent-disposition: attachment\nContent-Transfer-Encoding: base64", $dataStr );
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

  public function data()
  {
    return $this->headers(). $this->body();
  }

  /// Sends the e-mail message uing the Mailer class, provided for convenience.
  /// @param $priority [int] (optional) queue priority when Config::$mailQueue is enabled
  /// @see Mailer::send()
  public function send( $priority = 10 )
  {
    Mailer::send( $this, $priority );
  }
}

?>