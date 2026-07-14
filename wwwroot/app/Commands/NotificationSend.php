<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\NotificationModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Process pending email notifications (transactional outbox).
 *
 * Collects PENDING notifications ordered by priority, sends them
 * via email, and updates status to SENT or FAILED. Handles retries
 * and dead-letter queue for permanently failed notifications.
 *
 * Designed to run as a cron job every minute:
 *   * * * * * cd /path/to/wwwroot && php spark notifications:send
 *
 * @package App\Commands
 * @author  Aythami
 * @since   1.4.0
 */
class NotificationSend extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'notifications:send';
    protected $description = 'Process and send pending email notifications from the outbox.';

    private NotificationModel $model;

    /**
     * Execute the command.
     *
     * @param array<int, string> $params CLI parameters
     */
    public function run(array $params): void
    {
        $this->model = model(NotificationModel::class);

        $pending = $this->model->findPending();

        if ($pending === []) {
            CLI::write('No pending notifications.', 'green');

            return;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($pending as $notification) {
            CLI::write("Sending: {$notification->subject} → {$notification->recipientEmail}", 'yellow');

            try {
                $this->model->markAsSending($notification);

                $success = $this->sendEmail($notification);

                if ($success) {
                    $this->model->markAsSent($notification, 'local-' . uniqid());
                    CLI::write('  Sent ✓', 'green');
                    $sent++;
                } else {
                    $this->handleFailure($notification, 'Email service returned failure.');
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->handleFailure($notification, $e->getMessage());
                $failed++;
            }
        }

        CLI::write("Done. Sent: {$sent}, Failed: {$failed}", $sent > 0 ? 'green' : 'yellow');
    }

    /**
     * Send an email via the configured mail service.
     *
     * In MVP, uses PHP's mail() function. In production this would
     * use an SMTP service (SendGrid, SES, etc.).
     *
     * @return bool True if sent successfully
     */
    private function sendEmail($notification): bool
    {
        $to      = $notification->recipientEmail;
        $subject = $notification->subject;
        $message = $notification->bodyText ?? '';

        if (! empty($notification->bodyHtml)) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message  = $notification->bodyHtml;
        } else {
            $headers  = "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        $headers .= 'From: ' . (env('email.fromEmail') ?? 'noreply@marachain.local') . "\r\n";
        $headers .= 'X-Mailer: MARAChain/1.4.0' . "\r\n";

        // Suppress mail() warnings in development (no SMTP configured)
        return @mail($to, $subject, $message, $headers);
    }

    /**
     * Handle a failed notification: increment attempts or move to dead-letter.
     */
    private function handleFailure($notification, string $error): void
    {
        CLI::write("  Failed ✗ — {$error}", 'red');

        $this->model->markAsFailed($notification, $error);
        $this->model->incrementAttemptCount($notification);
    }
}
