<?php

namespace Wopits;

require_once (__DIR__.'/../config.php');

class Message
{
  private $_mailer;

  public function __construct (bool $persist = false)
  {
    $this->_mailer = new \PHPMailer\PHPMailer\PHPMailer ();

    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $this->_mailer->CharSet = 'UTF-8';
    $this->_mailer->Encoding = 'quoted-printable';
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
      'subject' => _("Creation of your wopits account"),
      'msg' => sprintf(_("Hello %s").",\n\n"._("Your account «%s» has been created!"), $args['fullname'], $args['username'])
    ]);
  }

  private function accountInactive (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Your wopits account"),
      'msg' => sprintf(_("Hello %s").",\n\n"._("It has been over a year since you used wopits.\n\nYour account «%s» will be automatically deleted in 4 weeks if you still don't come back."), $args['fullname'], $args['username'])
    ], true);
  }

  private function accountAutoDeleted (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Your wopits account has been deleted"),
      'msg' => sprintf(_("Hello %s").",\n\n"._("It's been a long time since you used wopits.\n\nDespite our last message you did not come back.\n\nYour account «%s» has been automatically deleted."), $args['fullname'], $args['username'])
    ], true);
  }

  private function resetPassword (array $args):void
  {
    $this->_send ([
      'email' => $args['email'],
      'subject' => _("Your password reset"),
      'msg' => sprintf(_("Hello %s").",\n\n"._("You are receiving this email because you requested the reset of your wopits password.\n\n- Login: %s\n- New password: %s\n\nFor security reasons we advise you to change it as soon as possible."), $args['fullname'], $args['username'], $args['password'])
    ]);
  }

  private function notifyWorker (array $args):void
  {
    $subject = _("Note assignation");
    $qTitle = ($args['title'] != '...') ? $args['title'] : _("No title");

    $this->_addToQueue ([
      'users_id' => $args['userId'],
      'title' => $subject,
      'content' => sprintf (_("%s assigned you the note «%s»").'.', $args['sharerName'], "<a href='#' data-type='worker' data-wallid='{$args['wallId']}' data-postitid='{$args['postitId']}'>".htmlentities($qTitle)."</a>")
    ]);

    Helper::sendToWSServer ([
      'action' => 'have-msg',
      'wallId' => $args['wallId'],
      'users' => [$args['userId']]
    ]);

    if ($args['sendmail'])
      $this->_send ([
        'email' => $args['email'],
        'subject' => $subject,
        'msg' => sprintf (_("Hello %s").",\n\n"._("%s assigned you the note «%s»").":\n\n%s", $args['fullname'], $args['sharerName'], $qTitle, WPT_URL."/?/w{$args['wallId']}p{$args['postitId']}")
      ]);
  }

  private function commentToUser (array $args):void
  {
    $subject = _("Comment");
    $qTitle = ($args['title'] != '...') ? $args['title'] : _("No title");

    $this->_addToQueue ([
      'users_id' => $args['userId'],
      'title' => $subject,
      'content' => sprintf (_("%s mentioned you in a comment to the note «%s»:")."\n\n<quote>%s</quote>", htmlentities($args['sharerName']), "<a href='#' data-type='comment' data-wallid='{$args['wallId']}' data-postitid='{$args['postitId']}' data-commentid='{$args['commentId']}'>".htmlentities($qTitle)."</a>", htmlentities($args['msg']))
    ]);

    Helper::sendToWSServer ([
      'action' => 'have-msg',
      'wallId' => $args['wallId'],
      'users' => [$args['userId']]
    ]);

    if ($args['sendmail'])
      $this->_send ([
        'email' => $args['email'],
        'subject' => $subject,
        'msg' => sprintf (_("Hello %s").",\n\n"._("%s mentioned you in a comment to the note «%s»:")."\n\n%s\n----------\n%s\n----------", $args['fullname'], $args['sharerName'], $qTitle, WPT_URL."/?/c{$args['wallId']}p{$args['postitId']}c{$args['commentId']}", $args['msg'])
      ]);
  }

  private function deadlineAlert_1 (array $args):void
  {
    $subject = _("Deadline");
    $qTitle = ($args['title'] != '') ? $args['title'] : _("No title");

    $this->_addToQueue ([
      'users_id' => $args['userId'],
      'title' => $subject,
      'content' => sprintf (_("The note «%s» has expired."), "<a href='#' data-type='postit' data-wallid='{$args['wallId']}' data-postitid='{$args['postitId']}'>".htmlentities($qTitle)."</a>")
    ]);

    Helper::sendToWSServer ([
      'action' => 'have-msg',
      'wallId' => $args['wallId'],
      'users' => [$args['userId']]
    ]);

    if ($args['sendmail'])
      $this->_send ([
        'email' => $args['email'],
        'subject' => $subject,
        'msg' => sprintf (_("Hello %s").",\n\n"._("The following note has expired:")."\n\n%s%s", $args['fullname'], ($args['title'] != '') ? "«{$args['title']}»\n":'', WPT_URL."/?/a{$args['wallId']}p{$args['postitId']}")
      ]);
  }

  private function deadlineAlert_2 (array $args):void
  {
    $days = $args['days'];
    $hours = $args['hours'];
    $subject = _("Deadline");
    $qTitle = ($args['title'] != '') ? $args['title'] : _("No title");

    $this->_addToQueue ([
      'users_id' => $args['userId'],
      'title' => $subject,
      'content' => ($days == 1 || ($days == 0 && $hours > 0)) ?
        sprintf (
          _("The note «%s» will expire soon."),
          "<a href='#' data-type='postit' data-wallid='{$args['wallId']}' data-postitid='{$args['postitId']}'>".htmlentities($qTitle)."</a>")
        :
        sprintf (
          _("The note «%s» will expire in %s days."),
          "<a href='#' data-type='postit' data-wallid='{$args['wallId']}' data-postitid='{$args['postitId']}'>".htmlentities($qTitle)."</a>",
          $days)
    ]);

    Helper::sendToWSServer ([
      'action' => 'have-msg',
      'wallId' => $args['wallId'],
      'users' => [$args['userId']]
    ]);

    if ($args['sendmail'])
      $this->_send ([
        'email' => $args['email'],
        'subject' => $subject,
        'msg' => ($days == 1 || ($days == 0 && $hours > 0)) ?
          sprintf (
            _("Hello %s").",\n\n".
            _("The following note will expire soon:")."\n\n%s",
            $args['fullname'],
            WPT_URL."/?/a{$args['wallId']}p{$args['postitId']}")
          :
          sprintf (
            _("Hello %s").",\n\n".
            _("The following note will expire in %s days:")."\n\n%s%s",
            $args['fullname'],
            $days,
            ($args['title'] != '')?"«{$args['title']}»\n":'',
            WPT_URL."/?/a{$args['wallId']}p{$args['postitId']}")
      ]);
  }

  private function wallSharing (array $args):void
  {
    $subject = _("Wall sharing");
    $msg = '';

    switch ($args['access'])
    {
      case WPT_WRIGHTS_ADMIN:
        $msg = _("%s gave you full access to the wall «%s»");
        break;
      case WPT_WRIGHTS_RW:
        $msg = _("%s gave you limited access with creation of notes to the wall «%s»");
        break;
      case WPT_WRIGHTS_RO:
        $msg = _("%s gave you read-only access to the wall «%s»");
        break;
    }

    $this->_addToQueue ([
      'users_id' => $args['userId'],
      'title' => $subject,
      'content' => sprintf ("$msg.",
        $args['sharerName'],
        "<a href='#' data-type='wall' data-wallid='{$args['wallId']}'>".htmlentities($args['wallTitle'])."</a>",'')
    ]);

    if ($args['sendmail'])
      $this->_send ([
        'email' => $args['email'],
        'subject' => $subject,
        'msg' => sprintf (_("Hello %s").",\n\n"."$msg:\n\n%s",
          $args['recipientName'],
          $args['sharerName'],
          "{$args['wallTitle']}",
          WPT_URL."/?/s{$args['wallId']}")
      ]);
  }

  private function _addToQueue (array $args):void
  {
    $args['creationdate'] = time ();
    (new Base())->executeQuery ('INSERT INTO messages_queue', $args);
  }

  private function _send (array $args, bool $noUnsub = false):void
  {
    //<WPTPROD-remove>
    if (WPT_DEV_MODE)
      $args['email'] = WPT_EMAIL_CONTACT;
    //</WPTPROD-remove>

    try
    {
      $unsub = WPT_URL.'/?/unsubscribe';

      $this->_mailer->addAddress ($args['email']);
      $this->_mailer->Subject = $args['subject'];
      $this->_mailer->Body = $args['msg']."\n\n--\n";

      $this->_mailer->Body .=
        $noUnsub ? WPT_URL : _("Manage my options").": $unsub";

      $this->_mailer->addCustomHeader ('List-Unsubscribe',
        '<mailto:'.WPT_EMAIL_UNSUBSCRIBE.'?subject=Unsubscribe>, <'.$unsub.'>');

      $this->_mailer->send ();
    }
    catch (\Exception $e)
    {
      $msg = 'Message could not be sent. Email Error: '.
               $this->_mailer->ErrorInfo;
      error_log ($msg);
      throw new \Exception ($msg);
    }
  }
}
