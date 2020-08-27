<?php

namespace Wopits;

require_once (__DIR__.'/../prepend.php');

use PHPMailer\PHPMailer\PHPMailer;

use Wopits\Common;
use Wopits\Base;
use Wopits\User;

class EmailsQueue extends Base
{
  public function addTo ($data)
  {
    if (isset ($data['data']))
      $data['data'] = json_encode ($data['data']);

    if ($data['item_type'] == 'resetPassword')
    {
      $this
        ->prepare("
          DELETE FROM emails_queue
          WHERE item_type = ?
            AND users_id = ?")
        ->execute ([$data['item_type'], $data['users_id']]);
    }

    $this->executeQuery ('INSERT INTO emails_queue', $data);
  }

  public function process ()
  {
    $oldTZ = date_default_timezone_get ();

    $i = time ();
    $this->exec ("UPDATE emails_queue SET processed = $i WHERE processed = 0");

    $stmtDelete = $this->prepare ('DELETE FROM emails_queue WHERE id = ?');

    foreach ($this->query ("
      SELECT * FROM emails_queue WHERE processed = $i ORDER BY id ASC")
        as $item)
    {
      try
      {
        $User = new User (['userId' => $item['users_id']]);

        Common::changeLocale (Common::getsLocale ($User));
        date_default_timezone_set ($User->getTimezone ());

        $data = json_decode ($item['data']??'{}');

        foreach (['walls_id', 'groups_id', 'postits_id'] as $k)
          if (isset ($item[$k]))
            $data->$k = $item[$k];

        $this->{$item['item_type']}($User->getUser()['email'], $data);

        $stmtDelete->execute ([$item['id']]);
      }
      catch (\Exception $e)
      {
        $this->exec ("
          UPDATE emails_queue SET processed = 0 WHERE processed = $i");
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      }
    }

    date_default_timezone_set ($oldTZ);
  }

  private function accountCreation ($to, $data)
  {
    $this->send ([
      'email' => $to,
      'subject' => _("Creation of your account"),
      'msg' => sprintf(_("Hello %s,\n\nYour account \"%s\" has been created!"), $data->fullname, $data->username)
    ]);
  }

  private function resetPassword ($to, $data)
  {
    $this->send ([
      'email' => $to,
      'subject' => _("Your password reset"),
      'msg' => sprintf(_("Hello %s,\n\nYou are receiving this email because you requested the reset of your wopits password.\n\n- Login: %s\n- New password: %s\n\nFor security reasons we advise you to change it as soon as possible."), $data->fullname, $data->username, $data->password)
    ]);
  }

  private function deadlineAlert_1 ($to, $data)
  {
    $this->send ([
      'email' => $to,
      'subject' => _("Sticky note deadline notification"),
      'msg' => sprintf (_("Hello %s,\n\nThe following sticky note has expired:\n\n%s%s"), $data->fullname, ($data->title != '') ? "«{$data->title}»\n":'', WPT_URL."/?/a/{$data->walls_id}/{$data->postits_id}")
    ]);
  }

  private function deadlineAlert_2 ($to, $data)
  {
    $days = $data->days;
    $hours = $data->hours;

    $this->send ([
      'email' => $to,
      'subject' => _("Sticky note deadline notification"),
      'msg' => ($days == 1 || ($days == 0 && $hours > 0)) ?
        sprintf (_("Hello %s,\n\nThe following sticky note will expire soon:\n\n%s"), $data->fullname, WPT_URL."/?/a/{$data->walls_id}/{$data->postits_id}") : sprintf (_("Hello %s,\n\nThe following sticky note will expire in %s days:\n\n%s%s"), $data->fullname, $days, ($data->title != '') ? "«{$data->title}»\n":'', WPT_URL."/?/a/{$data->walls_id}/{$data->postits_id}")
    ]);
  }

  private function wallSharing ($to, $data)
  {
    $msg = '';

    switch ($data->access)
    {
      case WPT_WRIGHTS_ADMIN:
        $msg = _("Hello %s,\n\n%s gave you full access to the following wall:\n\n%s\n%s");
        break;
      case WPT_WRIGHTS_RW:
        $msg = _("Hello %s,\n\n%s gave you limited access with creation of sticky notes to the following wall:\n\n%s\n%s");
        break;
      case WPT_WRIGHTS_RO:
        $msg = _("Hello %s,\n\n%s gave you read-only access to the following wall:\n\n%s\n%s");
        break;
    }

    $this->send ([
      'email' => $to,
      'subject' => _("Wall sharing"),
      'msg' => sprintf ($msg, $data->recipientName, $data->sharerName, "«{$data->wallTitle}»", WPT_URL."/?/s/{$data->walls_id}")
    ]);
  }

  private function send ($args)
  {
    //<WPTPROD-remove>
    if (WPT_DEV_MODE)
      $args['email'] = WPT_EMAIL_CONTACT;
    //</WPTPROD-remove>

    $mail = new PHPMailer (true);
    try
    {
      //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->CharSet = 'UTF-8';
      $mail->Encoding = 'base64';
      $mail->isHTML (false);

      if (defined ('WPT_SMTP_HOST') && !empty (WPT_SMTP_HOST))
      {
        $mail->isSMTP ();
        $mail->Host = WPT_SMTP_HOST;

        if (!empty (WPT_SMTP_PORT))
          $mail->Port = WPT_SMTP_PORT;
      }

      $mail->setFrom (WPT_EMAIL_FROM, 'wopits');
      $mail->addAddress ($args['email']);
      $mail->Subject = $args['subject'];

      if (WPT_USE_DKIM)
      {
        $mail->DKIM_domain = WPT_DKIM_DOMAIN;
        $mail->DKIM_private = __DIR__.'/../dkim/dkim.private';
        $mail->DKIM_selector = WPT_DKIM_SELECTOR;
        $mail->DKIM_passphrase = '';
        $mail->DKIM_identity = $mail->From;
      }

      $mail->Body =
        $args['msg'].
        "\n\n"._("The wopits team,")."\n\n--\n".
        _("Message sent automatically.")."\n".WPT_URL;

      $mail->send ();
    }
    catch (\Exception $e)
    {
      throw new \Exception (
        "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
  }
}
