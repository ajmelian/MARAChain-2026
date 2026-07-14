<?php

declare(strict_types=1);

namespace App\Commands;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\Providers\EmailNotificationProvider;
use App\Notifications\Providers\WhatsAppNotificationProvider;
use App\Notifications\Providers\TelegramNotificationProvider;
use App\Notifications\Providers\SmsNotificationProvider;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\RecipientAddress;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * NotificationsCommand — Procesa el outbox de notificaciones.
 *
 * Lee registros QUEUED/RETRYING de notification_requested,
 * los envia via el proveedor correspondiente y actualiza el estado.
 *
 * Cron: * * * * * cd /path && php spark notifications:send
 *
 * @package App\Commands
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class NotificationsCommand extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'notifications:send';
    protected $description = 'Procesa el outbox de notificaciones y las envia por los canales configurados.';

    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            NotificationChannel::EMAIL->value    => new EmailNotificationProvider(),
            NotificationChannel::WHATSAPP->value => new WhatsAppNotificationProvider(),
            NotificationChannel::TELEGRAM->value => new TelegramNotificationProvider(),
            NotificationChannel::SMS->value      => new SmsNotificationProvider(),
        ];
    }

    public function run(array $params): void
    {
        $db     = db_connect();
        $table  = $db->prefixTable('notification_requested');

        $rows = $db->table('notification_requested')
            ->whereIn('status', ['QUEUED', 'RETRYING'])
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->orWhere('scheduled_at', null)
            ->orderBy('priority', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->limit(50)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            CLI::write('No pending notifications.', 'green');

            return;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $channel = NotificationChannel::tryFrom($row['channel']);
            if ($channel === null) {
                $this->updateStatus($db, $table, $row['id'], 'FAILED', 'UNKNOWN_CHANNEL', 'Canal no reconocido: ' . $row['channel']);
                $failed++;
                continue;
            }

            $provider = $this->providers[$channel->value] ?? null;
            if ($provider === null) {
                $this->updateStatus($db, $table, $row['id'], 'FAILED', 'NO_PROVIDER', 'No hay proveedor para el canal: ' . $channel->value);
                $failed++;
                continue;
            }

            CLI::write(sprintf('[%s] %s → %s', strtoupper($channel->value), $row['subject'], $row['recipient_address']), 'yellow');

            // Mark as SENDING
            $this->updateAttempt($db, $table, $row['id'], 'SENDING');

            try {
                $result = $provider->send(
                    new RecipientAddress($channel, $row['recipient_address']),
                    new NotificationMessage(
                        $row['subject'],
                        $row['body_text'] ?? '',
                        $row['body_html'] ?? null
                    )
                );

                if ($result->success) {
                    $db->table('notification_requested')
                        ->where('id', $row['id'])
                        ->update([
                            'status'              => 'SENT',
                            'sent_at'             => date('Y-m-d H:i:s'),
                            'provider_message_id' => $result->providerMessageId,
                            'updated_at'          => date('Y-m-d H:i:s'),
                        ]);
                    CLI::write('  Sent OK ✓', 'green');
                    $sent++;
                } else {
                    $this->handleFailure($db, $table, $row, $result->errorCode ?? 'UNKNOWN', $result->errorMessage ?? 'Error desconocido.');
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->handleFailure($db, $table, $row, 'EXCEPTION', $e->getMessage());
                $failed++;
            }
        }

        CLI::write(sprintf('Done. Sent: %d, Failed: %d', $sent, $failed), $sent > 0 ? 'green' : 'yellow');
    }

    private function handleFailure($db, string $table, array $row, string $errorCode, string $errorMessage): void
    {
        $newCount    = (int) $row['attempt_count'] + 1;
        $maxAttempts = (int) $row['max_attempts'];
        $newStatus   = $newCount >= $maxAttempts ? 'DEAD_LETTER' : 'FAILED';

        $db->table('notification_requested')
            ->where('id', $row['id'])
            ->update([
                'status'          => $newStatus,
                'attempt_count'   => $newCount,
                'error_code'      => $errorCode,
                'error_message'   => $errorMessage,
                'last_attempt_at' => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

        CLI::write("  Failed ✗ — {$errorCode}: {$errorMessage}" . ($newStatus === 'DEAD_LETTER' ? ' [DEAD_LETTER]' : ''), 'red');
    }

    private function updateAttempt($db, string $table, string $id, string $status): void
    {
        $db->table('notification_requested')
            ->where('id', $id)
            ->update([
                'status'          => $status,
                'last_attempt_at' => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
    }

    private function updateStatus($db, string $table, string $id, string $status, string $errorCode, string $errorMessage): void
    {
        $db->table('notification_requested')
            ->where('id', $id)
            ->update([
                'status'        => $status,
                'error_code'    => $errorCode,
                'error_message' => $errorMessage,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
    }
}
