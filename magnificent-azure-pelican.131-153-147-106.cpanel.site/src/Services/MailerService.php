<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

final class MailerService
{
    public function __construct(private readonly array $config)
    {
    }

    public function sendCustomerConfirmation(array $reservation): void
    {
        $body = implode("\n", [
            'Hello ' . $reservation['full_name'] . ',',
            '',
            'Thank you for your reservation.',
            '',
            'Your reservation has been received successfully.',
            '',
            'Reservation ID: ' . $reservation['reservation_id'],
            '',
            'Event Date: ' . $reservation['event_date'],
            '',
            'Our team will contact you if additional information is required.',
            '',
            'Thank you.',
        ]);

        $this->sendMessage(
            $reservation['email'],
            'Reservation Confirmation',
            $body
        );
    }

    public function sendOrganizerNotification(array $reservation): void
    {
        $body = implode("\n", [
            'A new reservation has been submitted.',
            '',
            'Reservation ID: ' . $reservation['reservation_id'],
            '',
            'Customer Name: ' . $reservation['full_name'],
            'Email: ' . $reservation['email'],
            'Phone: ' . $reservation['phone'],
            'Event Date: ' . $reservation['event_date'],
            'Event Type: ' . ($reservation['event_type'] ?? ''),
            '',
            'Please review the reservation details.',
        ]);

        $this->sendMessage(
            $this->config['organizer_email'],
            'New Reservation Received',
            $body
        );
    }

    private function sendMessage(string $to, string $subject, string $body): void
    {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';

            if ($this->config['mailer'] === 'smtp' && !empty($this->config['host'])) {
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->Port = (int) $this->config['port'];
                $mail->SMTPAuth = !empty($this->config['username']);
                $mail->Username = (string) $this->config['username'];
                $mail->Password = (string) $this->config['password'];

                if (!empty($this->config['encryption'])) {
                    $mail->SMTPSecure = (string) $this->config['encryption'];
                }
            } else {
                $mail->isMail();
            }

            $mail->setFrom(
                (string) $this->config['from_address'],
                (string) $this->config['from_name']
            );
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;
            $mail->isHTML(false);
            $mail->send();
        } catch (Throwable $throwable) {
            throw new RuntimeException('Email delivery failed: ' . $throwable->getMessage(), 0, $throwable);
        }
    }
}
