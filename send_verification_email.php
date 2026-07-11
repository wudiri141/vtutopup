<?php

use PHPMailer\PHPMailer\PHPMailer;

$phpmailerBase = __DIR__ . '/phpmailer/src';
if (file_exists($phpmailerBase . '/Exception.php')
    && file_exists($phpmailerBase . '/PHPMailer.php')
    && file_exists($phpmailerBase . '/SMTP.php')) {
    require_once $phpmailerBase . '/Exception.php';
    require_once $phpmailerBase . '/PHPMailer.php';
    require_once $phpmailerBase . '/SMTP.php';
}

function emailLog($message)
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    error_log($message);
}

function deliverHtmlEmail($email, $fullname, $subject, $body, $altBody = '')
{
    if (class_exists(PHPMailer::class)) {
        try {
            $mail = baseMailer();
            $mail->addAddress($email, $fullname);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            $mail->send();
            emailLog("Sent via SMTP to {$email}: {$subject}");
            return true;
        } catch (Throwable $e) {
            emailLog("PHPMailer failed for {$email}: {$subject} - " . $e->getMessage());
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: VTUTOPUP <support@vtutopup.com.ng>',
    ];

    $sent = mail($email, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        emailLog("PHP mail failed for {$email}: {$subject}");
    } else {
        emailLog("Sent via PHP mail to {$email}: {$subject}");
    }
    return $sent;
}

function baseMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'mail.vtutopup.com.ng';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@vtutopup.com.ng';
    $mail->Password   = 'Adamusani141@121';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    $mail->setFrom('support@vtutopup.com.ng', 'VTUTOPUP');
    $mail->addReplyTo('support@vtutopup.com.ng', 'VTUTOPUP Support');
    $mail->Sender = 'support@vtutopup.com.ng';
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    return $mail;
}

function sendVerificationEmail($email, $fullname, $token)
{
    try {
        $verify_link = "https://vtutopup.com.ng/verify_email.php?token=" . urlencode($token);

        $body = "
        <div style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
        <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:12px;padding:30px;'>

            <div style='text-align:center;margin-bottom:20px;'>
            <img src='https://vtutopup.com.ng/assets/logo-transparent.png' alt='VTUTOPUP' style='width:120px;height:auto;display:block;margin:auto;'>
            </div>

            <h2 style='margin:0 0 15px 0;color:#111827;text-align:center;'>Verify Your Email</h2>

            <p style='font-size:15px;color:#374151;'>Hello {$fullname},</p>

            <p style='font-size:15px;color:#374151;line-height:1.6;'>
            Welcome to VTUTOPUP. Please verify your email address to activate your account.
            </p>

            <div style='text-align:center;margin:25px 0;'>
            <a href='{$verify_link}' style='background:#1E9BD7;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;display:inline-block;'>
                Verify Email
            </a>
            </div>

            <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
            If the button does not work, copy and open this link:
            </p>

            <p style='font-size:14px;color:#2563eb;word-break:break-word;'>{$verify_link}</p>

            <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
            If you did not create this account, you can ignore this email.
            </p>

            <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>

            <p style='font-size:12px;color:#9ca3af;text-align:center;'>
            VTUTOPUP • Secure VTU Services
            </p>

        </div>
        </div>
        ";
        return deliverHtmlEmail($email, $fullname, 'Verify your email - VTUTOPUP', $body, "Hello {$fullname}, verify your email here: {$verify_link}");
    } catch (Throwable $e) {
        return false;
    }
}

function sendPasswordResetEmail($email, $fullname, $token)
{
    try {
        $reset_link = "https://vtutopup.com.ng/reset_password.php?token=" . urlencode($token);

        $body = "
                <div style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
                <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:12px;padding:30px;'>

                    <div style='text-align:center;margin-bottom:20px;'>
                    <img src='https://vtutopup.com.ng/assets/logo-transparent.png' alt='VTUTOPUP' style='width:120px;height:auto;display:block;margin:auto;'>
                    </div>

                    <h2 style='margin:0 0 15px 0;color:#111827;text-align:center;'>Reset Your Password</h2>

                    <p style='font-size:15px;color:#374151;'>Hello {$fullname},</p>

                    <p style='font-size:15px;color:#374151;line-height:1.6;'>
                    You requested to reset your password for your VTUTOPUP account.
                    </p>

                    <p style='font-size:15px;color:#374151;line-height:1.6;'>
                    Click the button below to create a new password:
                    </p>

                    <div style='text-align:center;margin:25px 0;'>
                    <a href='{$reset_link}' style='background:#1E9BD7;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;display:inline-block;'>
                        Reset Password
                    </a>
                    </div>

                    <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
                    If the button does not work, copy and open this link:
                    </p>

                    <p style='font-size:14px;color:#2563eb;word-break:break-word;'>{$reset_link}</p>

                    <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
                    This link will expire soon. If you did not request this, you can ignore this email.
                    </p>

                    <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>

                    <p style='font-size:12px;color:#9ca3af;text-align:center;'>
                    VTUTOPUP • Secure VTU Services
                    </p>

                </div>
                </div>
                ";
        return deliverHtmlEmail($email, $fullname, 'Reset your password - VTUTOPUP', $body, "Hello {$fullname}, reset your password here: {$reset_link}");
    } catch (Throwable $e) {
        return false;
    }
}

function sendLoginOtpEmail($email, $fullname, $otp)
{
    try {
        $body = "
        <div style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
        <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:12px;padding:30px;'>

            <div style='text-align:center;margin-bottom:20px;'>
            <img src='https://vtutopup.com.ng/assets/logo-transparent.png' alt='VTUTOPUP' style='width:120px;height:auto;display:block;margin:auto;'>
            </div>

            <h2 style='margin:0 0 15px 0;color:#111827;text-align:center;'>Login Verification</h2>

            <p style='font-size:15px;color:#374151;'>Hello {$fullname},</p>

            <p style='font-size:15px;color:#374151;line-height:1.6;'>
            Use the OTP below to complete your login:
            </p>

            <div style='text-align:center;margin:25px 0;'>
            <div style='display:inline-block;background:#eff6ff;color:#1d4ed8;padding:14px 24px;border-radius:10px;font-size:28px;font-weight:bold;letter-spacing:6px;'>
                {$otp}
            </div>
            </div>

            <p style='font-size:14px;color:#6b7280;line-height:1.6;text-align:center;'>
            This OTP will expire in 10 minutes.
            </p>

            <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
            If you did not try to log in, please change your password immediately.
            </p>

            <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>

            <p style='font-size:12px;color:#9ca3af;text-align:center;'>
            VTUTOPUP • Secure VTU Services
            </p>

        </div>
        </div>
        ";
        return deliverHtmlEmail($email, $fullname, 'Your Login OTP - VTUTOPUP', $body, "Hello {$fullname}, your login OTP is {$otp}");
    } catch (Throwable $e) {
        return false;
    }
}

function sendWelcomeEmail($email, $fullname)
{
    try {
        $dashboard_link = "https://vtutopup.com.ng/login.php";

        $body = "
        <div style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
        <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:12px;padding:30px;'>

            <div style='text-align:center;margin-bottom:20px;'>
            <img src='https://vtutopup.com.ng/assets/logo-transparent.png' alt='VTUTOPUP' style='width:120px;height:auto;display:block;margin:auto;'>
            </div>

            <h2 style='margin:0 0 15px 0;color:#111827;text-align:center;'>Welcome to VTUTOPUP</h2>

            <p style='font-size:15px;color:#374151;'>Hello {$fullname},</p>

            <p style='font-size:15px;color:#374151;line-height:1.6;'>
            Your email has been verified successfully. Your VTUTOPUP account is now active.
            </p>

            <div style='text-align:center;margin:25px 0;'>
            <a href='{$dashboard_link}' style='background:#1E9BD7;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;display:inline-block;'>
                Sign In
            </a>
            </div>

            <p style='font-size:14px;color:#6b7280;line-height:1.6;'>
            You can now fund your wallet, buy airtime, buy data, and pay bills securely.
            </p>

            <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>

            <p style='font-size:12px;color:#9ca3af;text-align:center;'>
            VTUTOPUP • Secure VTU Services
            </p>

        </div>
        </div>
        ";

        return deliverHtmlEmail($email, $fullname, 'Welcome to VTUTOPUP', $body, "Hello {$fullname}, welcome to VTUTOPUP. Your email has been verified successfully.");
    } catch (Throwable $e) {
        return false;
    }
}

function sendTransactionEmail($email, $fullname, $service, $amount, $reference)
{
    try {
        $body = "

       <div style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
                <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:12px;padding:30px;'>

                    <div style='text-align:center;margin-bottom:20px;'>
                    <img src='https://vtutopup.com.ng/assets/logo-transparent.png' alt='VTUTOPUP' style='width:120px;height:auto;display:block;margin:auto;'>
                    </div>
                <h2 style='color:#28a745;text-align:center;'>Payment Successful</h2>

                <p>Hello <strong>$fullname</strong>,</p>

                <p>Your transaction has been completed successfully.</p>

                <table style='width:100%;border-collapse:collapse;margin-top:20px;'>

                    <tr>
                        <td style='padding:10px;border-bottom:1px solid #eee;'><strong>Service</strong></td>
                        <td style='padding:10px;border-bottom:1px solid #eee;'>$service</td>
                    </tr>

                    <tr>
                        <td style='padding:10px;border-bottom:1px solid #eee;'><strong>Amount</strong></td>
                        <td style='padding:10px;border-bottom:1px solid #eee;'>₦$amount</td>
                    </tr>

                    <tr>
                        <td style='padding:10px;border-bottom:1px solid #eee;'><strong>Reference</strong></td>
                        <td style='padding:10px;border-bottom:1px solid #eee;'>$reference</td>
                    </tr>

                    <tr>
                        <td style='padding:10px;border-bottom:1px solid #eee;'><strong>Date</strong></td>
                        <td style='padding:10px;border-bottom:1px solid #eee;'>".date("Y-m-d H:i:s")."</td>
                    </tr>

                </table>

                <div style='text-align:center;margin-top:25px;'>

                    <a href='https://vtutopup.com.ng/dashboard.php' 
                    style='background:#0d6efd;color:white;padding:12px 20px;border-radius:5px;text-decoration:none;'>

                    View Dashboard

                    </a>

                </div>

                <p style='margin-top:30px;font-size:13px;color:#888;text-align:center;'>

                    This is an automated receipt from VTUTOPUP.

                </p>

            </div>

        </div>

        ";

        return deliverHtmlEmail($email, $fullname, 'Transaction Receipt - VTUTOPUP', $body, "Hello {$fullname}, your {$service} transaction of {$amount} was completed. Reference: {$reference}");

    } catch (Throwable $e) {

        return false;

    }
}
