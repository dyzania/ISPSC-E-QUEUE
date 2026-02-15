<?php
require_once __DIR__ . '/../config/config.php';
require_once MAILER_PATH;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailService {
    private function getMailer() {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            return $mail;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            return null;
        }
    }

    public function sendVerification($toEmail, $fullName, $token) {
        $mail = $this->getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - ' . APP_NAME;
            
            $verifyUrl = BASE_URL . "/verify.php?token=" . $token;
            
            $mail->Body    = "
                <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; rounded: 10px;'>
                    <h2 style='color: #15803d;'>Welcome to " . APP_NAME . "!</h2>
                    <p>Hello $fullName,</p>
                    <p>Please click the button below to verify your email address and activate your account:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$verifyUrl' style='background-color: #15803d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email Address</a>
                    </div>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='$verifyUrl'>$verifyUrl</a></p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #666;'>If you did not create an account, no further action is required.</p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Verification Email Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendTicketCalled($toEmail, $fullName, $ticketNumber, $windowName) {
        $mail = $this->getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Your Turn! Ticket #' . $ticketNumber;
            
            $mail->Body    = "
                <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; rounded: 10px;'>
                    <h2 style='color: #15803d;'>It's Your Turn!</h2>
                    <p>Hello $fullName,</p>
                    <p>Your ticket <strong>#$ticketNumber</strong> is now being called at:</p>
                    <h3 style='color: #15803d; background: #f0fdf4; padding: 15px; border-radius: 8px; text-align: center;'>$windowName</h3>
                    <p>Please proceed to the counter immediately. Thank you for waiting.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #666;'>Automatic notification from " . APP_NAME . ".</p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Calling Notification Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendTicketCompleted($toEmail, $fullName, $ticketNumber, $staffNotes = null) {
        $mail = $this->getMailer();
        if (!$mail) return false;

        try {
            $mail->addAddress($toEmail, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Transaction Completed - ' . $ticketNumber;
            
            $notesHtml = $staffNotes ? "
                <div style='margin-top: 20px; padding: 15px; background-color: #f8fafc; border-left: 4px solid #15803d; border-radius: 4px;'>
                    <p style='margin: 0; font-size: 14px; color: #475569; font-weight: bold;'>Message from Counter:</p>
                    <p style='margin: 5px 0 0; color: #1e293b;'>$staffNotes</p>
                </div>
            " : "";

            $mail->Body    = "
                <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #15803d;'>Transaction Completed</h2>
                    <p>Hello $fullName,</p>
                    <p>Your transaction for ticket <strong>#$ticketNumber</strong> has been successfully completed.</p>
                    $notesHtml
                    <p style='margin-top: 20px;'>We value your feedback! Please tell us how we did by rating your experience on our portal.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #666;'>Thank you for using " . APP_NAME . ".</p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Completion Notification Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
