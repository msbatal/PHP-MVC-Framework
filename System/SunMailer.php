<?php

/**
 * This file is part of the Sunhill Framework package.
 */

/**
 * Thin wrapper around the vendored PHPMailer (System/Vendor/PHPMailer).
 * SMTP settings come from the environment (.env), never hardcoded - see
 * .env.example. Used by App/Controllers/Auth.php for password-reset and
 * email-verification-code messages.
 */
class SunMailer
{
    private $mailer;

    public function __construct() {
        require_once (SYS_BASEPATH . '/System/Vendor/PHPMailer/Exception.php');
        require_once (SYS_BASEPATH . '/System/Vendor/PHPMailer/PHPMailer.php');
        require_once (SYS_BASEPATH . '/System/Vendor/PHPMailer/SMTP.php');
        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isSMTP();
        $this->mailer->Host = getenv('SMTP_HOST');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USER');
        $this->mailer->Password = getenv('SMTP_PASS');
        $this->mailer->SMTPSecure = getenv('SMTP_SECURE') !== false ? getenv('SMTP_SECURE') : 'tls';
        $this->mailer->Port = getenv('SMTP_PORT') !== false ? (int) getenv('SMTP_PORT') : 587;
        $this->mailer->isHTML(true);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @param array  $options
     * @return boolean
     */
    public function send($to = null, $subject = null, $body = null, $altBody = null, $options = []) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->clearBCCs();
            $this->mailer->clearAttachments();

            $this->mailer->From = isset($options['from']) ? $options['from'] : getenv('SMTP_USER');
            $this->mailer->FromName = isset($options['fromName']) ? $options['fromName'] : 'Tawcu';
            $this->mailer->addAddress($to, isset($options['toName']) ? $options['toName'] : '');

            if (!empty($options['replyTo'])) {
                $this->mailer->addReplyTo($options['replyTo'], isset($options['replyToName']) ? $options['replyToName'] : '');
            }
            foreach (isset($options['bcc']) ? $options['bcc'] : [] as $bcc) {
                $this->mailer->addBCC($bcc);
            }
            foreach (isset($options['attachments']) ? $options['attachments'] : [] as $attachment) {
                $this->mailer->addAttachment($attachment);
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody;

            return $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('[Tawcu] SunMailer send failed: ' . $e->getMessage());
            return false;
        }
    }

}

?>
