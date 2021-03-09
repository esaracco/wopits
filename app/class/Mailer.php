<?php

namespace Wopits;

require_once (__DIR__.'/../config.php');

class Mailer
{
  private $_mailer;

  public function __construct (bool $persist = false)
  {
    $this->_mailer = new \PHPMailer\PHPMailer\PHPMailer ();

    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $this->_mailer->CharSet = 'UTF-8';
    $this->_mailer->Encoding = 'base64';
    $this->_mailer->isHTML (false);

    if (defined ('WPT_SMTP_HOST') && !empty (WPT_SMTP_HOST))
    {
      $this->_mailer->isSMTP ();
      $this->_mailer->Host = WPT_SMTP_HOST;

      if (!empty (WPT_SMTP_PORT))
         $this->_mailer->Port = WPT_SMTP_PORT;
     }

    if (WPT_USE_DKIM)
    {
      $this->_mailer->DKIM_domain = WPT_DKIM_DOMAIN;
      $this->_mailer->DKIM_private = __DIR__.'/../dkim/dkim.private';
      $this->_mailer->DKIM_selector = WPT_DKIM_SELECTOR;
      $this->_mailer->DKIM_identity = WPT_EMAIL_FROM;
      $this->_mailer->DKIM_passphrase = '';
    }

    $this->_mailer->setFrom (WPT_EMAIL_FROM, 'wopits');
  }

  public function send (array $args):void
  {
    $User = new User (['userId' => $args['userId']]);
    Helper::changeLocale ((json_decode($User->getSettings()))->locale);
    date_default_timezone_set ($User->getTimezone ());

    $this->{$args['method']}($args);
  }

  private function accountCreation (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Creation of your account"),
      'msg' => sprintf(_("Hello %s,\n\nYour account \"%s\" has been created!"), $args['fullname'], $args['username'])
    ]);
  }

  private function resetPassword (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Your password reset"),
      'msg' => sprintf(_("Hello %s,\n\nYou are receiving this email because you requested the reset of your wopits password.\n\n- Login: %s\n- New password: %s\n\nFor security reasons we advise you to change it as soon as possible."), $args['fullname'], $args['username'], $args['password'])
    ]);
  }

  private function deadlineAlert_1 (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Deadline notification"),
      'msg' => sprintf (_("Hello %s,\n\nThe following note has expired:\n\n%s%s"), $args['fullname'], ($args['title'] != '') ? "«{$args['title']}»\n":'', WPT_URL."/?/a/{$args['wallId']}/{$args['postitId']}")
    ]);
  }

  private function deadlineAlert_2 (array $args):void
  {
    $days = $args['days'];
    $hours = $args['hours'];

    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Deadline notification"),
      'msg' => ($days == 1 || ($days == 0 && $hours > 0)) ?
        sprintf (_("Hello %s,\n\nThe following note will expire soon:\n\n%s"), $args['fullname'], WPT_URL."/?/a/{$args['wallId']}/{$args['postitId']}") : sprintf (_("Hello %s,\n\nThe following note will expire in %s days:\n\n%s%s"), $args['fullname'], $days, ($args['title'] != '') ? "«{$args['title']}»\n":'', WPT_URL."/?/a/{$args['wallId']}/{$args['postitId']}")
    ]);
  }

  private function wallSharing (array $args):void
  {
    $msg = '';

    switch ($args['access'])
    {
      case WPT_WRIGHTS_ADMIN:
        $msg = _("Hello %s,\n\n%s gave you full access to the following wall:\n\n%s\n%s");
        break;
      case WPT_WRIGHTS_RW:
        $msg = _("Hello %s,\n\n%s gave you limited access with creation of notes to the following wall:\n\n%s\n%s");
        break;
      case WPT_WRIGHTS_RO:
        $msg = _("Hello %s,\n\n%s gave you read-only access to the following wall:\n\n%s\n%s");
        break;
    }

    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Wall sharing"),
      'msg' => sprintf ($msg, $args['recipientName'], $args['sharerName'], "«{$args['wallTitle']}»", WPT_URL."/?/s/{$args['wallId']}")
    ]);
  }

  private function _send (array $args):void
  {
    //<WPTPROD-remove>
    if (WPT_DEV_MODE)
      $args['email'] = WPT_EMAIL_CONTACT;
    //</WPTPROD-remove>

    try
    {
      $this->_mailer->addAddress ($args['email']);
      $this->_mailer->Subject = $args['subject'];
      $this->_mailer->Body =
        $args['msg'].
        "\n\n"._("The wopits team,")."\n\n--\n".
        _("Message sent automatically.");

      $this->_mailer->send ();
    }
    catch (\Exception $e)
    {
      $msg = 'Message could not be sent. Mailer Error: '.
               $this->_mailer->ErrorInfo;
      error_log ($msg);
      throw new \Exception ($msg);
    }
  }
}
