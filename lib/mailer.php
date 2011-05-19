<?

require_once "Mail/RFC822.php";
require_once "Net/SMTP.php";

class Mailer
{
  private $from;
  private $to = array();
  private $subject;
  private $msg;
  private $signature;
  private $headers = array();
  private $html;
  private $attachments = array();
  private $msgId;
  private $mimeBoundary;

  public function __construct( $from, $to, $subject, $msg, $signature=null )
  {
    self::reset();

    self::setSender( $from );
    self::addRecipient( $to );

    $this->subject = $subject;
    $this->msg = $msg;
    $this->signature = $signature;

    self::setDefaultHeaders();
  }

  private function reset()
  {
    $this->msgId = "<". sha1(uniqid()). "@". $_SERVER['SERVER_NAME']. ">";
    $this->mimeBoundary = sha1(uniqid());
  }

  private function setDefaultHeaders()
  {
    self::createHeader( 'Return-Path', $this->from );
    self::createHeader( 'From', $this->from );
    self::createHeader( 'To', $this->to[0] );
    self::createHeader( 'Subject', $this->subject );
    self::createHeader( 'Message-ID', $this->msgId );
  }

  public function createHeader( $name, $value )
  {
    $this->addHeader( "$name: $value" );
  }

  public function addHeader( $header )
  {
    $this->headers[] = $header;
  }

  public function setSender( $from )
  {
    $this->from = $from;
  }

  public function addRecipient( $to )
  {
    $this->to[] = $to;
  }

  public function setHtml( $html )
  {
    $this->html = $html;
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

  private function headers()
  {
    $headers = array();
    foreach( $this->headers as $name => $value )
      $headers[]= "$name: $value";

    return implode( "\n", $this->headers). "\n";
  }

  private function body()
  {
    if ( $this->html )
    {
      $this->addHeader( self::multipartHeader($this->mimeBoundary, 'mixed', true) );
      $altBoundary = sha1(uniqid());
      return self::mimePart($this->mimeBoundary, self::multipartHeader($altBoundary, 'alternative') ).
        $this->textPart($altBoundary).
        $this->htmlPart($altBoundary).
        $this->mimePart($altBoundary).
        $this->AttachmentParts().
        self::mimePart($this->mimeBoundary);
    }
    else
    {
      if ( count($this->attachments) )
      {
        $this->addHeader( self::multipartHeader($this->mimeBoundary, 'mixed', true) );
        return $this->textPart($this->mimeBoundary). $this->attachmentParts(). self::mimePart($this->mimeBoundary);
      }
      else
      {
        return $this->textPart();
      }
    }
  }

  private function multipartHeader( $boundary, $type, $mimeVersion=false )
  {
    return ($mimeVersion ? "MIME-Version: 1.0\n" : "").
      "Content-Type: multipart/$type;\n\tboundary=\"". self::mimeBoundary($boundary). "\"";
  }

  private function mimeBoundary( $boundary, $asPart=false, $asEnd=false )
  {
    return "----". ($asPart ? "--" : ""). "=_Part_$boundary". ($asEnd ? "--" : "");
  }

  private function mimePart( $boundary, $headers=null, $data=null )
  {
    if ( $headers )
    {
      $part = self::mimeBoundary( $boundary, true );
      if ( $data )
        return "$part\n$headers\n\n$data\n\n";
      else
        return "$part\n$headers\n\n";
    }
    else
      return self::mimeBoundary( $boundary, true, true ). "\n\n";
  }

  private function textPart( $mimeBoundary=null )
  {
    $signaturePart = $this->signature ? ("\n-- \n". $this->signature) : null;

    if ( $mimeBoundary )
      return self::mimePart( $mimeBoundary, "Content-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: quoted-printable", $this->msg. $signaturePart );
    else
      return $this->msg. $signaturePart;
  }

  private function htmlPart( $boundary )
  {
    return $this->mimePart( $boundary, "Content-Type: text/html; charset=utf-8\nContent-Transfer-Encoding: 8bit", $this->html );
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

  public function send()
  {
    Log::debug( "Mailer: subject:[$this->subject], from:[$this->from], to:". print_r($this->to, true) );

    if ( !Config::$smtpHost && !Config::$smtpUser && !Config::$smtpPass )
    {
      $error = "Mailer: no SMTP credentials supplied";
      Log::debug($error);
      return $error;
    }
    
    if ( !($smtp = new Net_SMTP(Config::$smtpHost, Config::$smtpPort)) )
    {
      $error = "Mailer: unable to instantiate Net_SMTP object";
      Log::debug($error);
      return $error;
    }
    // $smtp->setDebug(true);

    if ( PEAR::isError($e = $smtp->connect()) )
    {
      $error = "Mailer: connect error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    if ( PEAR::isError($e = $smtp->auth(Config::$smtpUser, Config::$smtpPass, Config::$smtpAuthType)) )
    {
      $error = "Mailer: authentication error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    $rfc = new Mail_RFC822();
    $pureAddresses = $rfc->parseAddressList($this->from);
    $address = $pureAddresses[0];
    $email = $address->mailbox. "@". $address->host;

    if ( PEAR::isError($e = $smtp->mailFrom($email)))
    {
      $error = "Unable to set sender to [$email]: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    foreach( $this->to as $to )
    {
      $pureAddresses = $rfc->parseAddressList($to);
      foreach( $pureAddresses as $address )
      {
        $email = $address->mailbox. "@". $address->host;

        if ( PEAR::isError($e = $smtp->rcptTo($email)) )
        {
          $error = "Unable to set recipient to [$email]: ". $e->getMessage();
          Log::debug($error);
          return $error;
        }
      }
    }

    if ( PEAR::isError($e = $smtp->data($this->body(), $this->headers())) )
    {
      $error = "Unable to set data: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    Log::debug( "Mailer: sent: ". print_r($smtp->_arguments, true) );

    $smtp->disconnect();
  }
}

?>