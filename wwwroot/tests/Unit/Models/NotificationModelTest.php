<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Notification;
use App\Models\NotificationModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;

/**
 * Unit tests for NotificationModel.
 *
 * <p>RED phase: NotificationModel does not exist yet.
 * These tests define the expected contract and MUST FAIL until
 * the model is implemented.</p>
 *
 * <p>Notifications are managed via a transactional outbox pattern
 * with retries and dead-letter queue. MVP channel is email only.
 * Notifications never include document content, CID, NIF, keys,
 * or reusable tokens.</p>
 *
 * @coversNothing (model does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 */
final class NotificationModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /** @var bool */
    protected $refresh = true;

    /** @var string */
    protected $namespace = 'App';

    private NotificationModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        // This will fail because NotificationModel does not exist (RED phase)
        $this->model = new NotificationModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ────────────────────────────────────────────────
    //  CREATE
    // ────────────────────────────────────────────────

    /**
     * Creates a notification with all required fields.
     *
     * @test
     */
    public function testCreateNotification(): void
    {
        $data = [
            'recipientEmail'  => 'destinatario@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Nuevo documento disponible',
            'bodyText'         => 'Tiene un nuevo documento disponible para su descarga.',
            'status'           => 'PENDING',
            'priority'         => 'normal',
        ];

        $notification = $this->model->createNotification($data);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNotEmpty($notification->id);
        $this->assertSame('destinatario@example.com', $notification->recipientEmail);
        $this->assertSame('transfer_available', $notification->notificationType);
        $this->assertSame('PENDING', $notification->status);
        $this->assertSame('normal', $notification->priority);
        $this->assertSame(0, $notification->attemptCount);
        $this->assertSame(5, $notification->maxAttempts);
        $this->assertTrue($notification->isPending());
        $this->assertFalse($notification->isSent());
        $this->assertFalse($notification->isDeadLetter());
    }

    /**
     * Creating a notification without a recipientEmail must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutRecipientEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('recipientEmail');

        $data = [
            // recipientEmail omitted intentionally
            'notificationType' => 'transfer_available',
            'subject'          => 'Nuevo documento disponible',
            'bodyText'         => 'Tiene un nuevo documento disponible.',
        ];

        $this->model->createNotification($data);
    }

    // ────────────────────────────────────────────────
    //  FIND
    // ────────────────────────────────────────────────

    /**
     * Finds all notifications with PENDING status.
     *
     * @test
     */
    public function testFindPending(): void
    {
        $results = $this->model->findPending();

        $this->assertIsArray($results);

        foreach ($results as $notification) {
            $this->assertInstanceOf(Notification::class, $notification);
            $this->assertSame('PENDING', $notification->status);
        }
    }

    /**
     * Finds notifications by recipient email address with actual data.
     *
     * @test
     */
    public function testFindByRecipientEmail(): void
    {
        $email = 'destinatario@example.com';

        // Create notifications for the target email.
        $this->model->createNotification([
            'recipientEmail'   => $email,
            'notificationType' => 'transfer_available',
            'subject'          => 'First notification',
            'bodyText'         => 'First.',
            'priority'         => 'normal',
        ]);

        $this->model->createNotification([
            'recipientEmail'   => $email,
            'notificationType' => 'transfer_available',
            'subject'          => 'Second notification',
            'bodyText'         => 'Second.',
            'priority'         => 'high',
        ]);

        // Create a notification for a different email.
        $this->model->createNotification([
            'recipientEmail'   => 'other@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Other notification',
            'bodyText'         => 'Other.',
            'priority'         => 'normal',
        ]);

        $results = $this->model->findByRecipientEmail($email);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        foreach ($results as $notification) {
            $this->assertInstanceOf(Notification::class, $notification);
            $this->assertSame($email, $notification->recipientEmail);
        }
    }

    /**
     * Finds notifications by recipient email with no matches returns empty.
     *
     * @test
     */
    public function testFindByRecipientEmailNoMatch(): void
    {
        $results = $this->model->findByRecipientEmail('nonexistent@example.com');

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    /**
     * Finds notifications related to a specific transfer with actual data.
     *
     * @test
     */
    public function testFindByTransferId(): void
    {
        $transferId = 'a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6';

        // Notifications without transferId should not match the query
        $this->model->createNotification([
            'recipientEmail'   => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Transfer notification',
            'bodyText'         => 'You have a transfer.',
            'priority'         => 'normal',
        ]);

        $results = $this->model->findByTransferId($transferId);

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    /**
     * Finds notifications by transfer ID with no matches returns empty.
     *
     * @test
     */
    public function testFindByTransferIdNoMatch(): void
    {
        $results = $this->model->findByTransferId('00000000-0000-0000-0000-000000000000');

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    // ────────────────────────────────────────────────
    //  STATE TRANSITIONS
    // ────────────────────────────────────────────────

    /**
     * Marks a notification as SENDING (in transit to provider).
     *
     * @test
     */
    public function testMarkAsSending(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Test notification',
            'bodyText'         => 'Test body.',
            'priority'         => 'normal',
        ]);

        $this->assertSame('PENDING', $notification->status);

        $updated = $this->model->markAsSending($notification->id);

        $this->assertSame('SENDING', $updated->status);
        $this->assertFalse($updated->isPending());
    }

    /**
     * Marks a notification as SENT with provider message ID and timestamp.
     *
     * @test
     */
    public function testMarkAsSent(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Test notification',
            'bodyText'         => 'Test body.',
            'priority'         => 'normal',
        ]);

        $updated = $this->model->markAsSent(
            $notification->id,
            'provider-msg-12345'
        );

        $this->assertSame('SENT', $updated->status);
        $this->assertSame('provider-msg-12345', $updated->providerMessageId);
        $this->assertNotNull($updated->sentAt);
        $this->assertTrue($updated->isSent());
    }

    /**
     * Marks a notification as FAILED with an error message.
     *
     * @test
     */
    public function testMarkAsFailed(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Test notification',
            'bodyText'         => 'Test body.',
            'priority'         => 'normal',
        ]);

        $updated = $this->model->markAsFailed(
            $notification->id,
            'SMTP connection timeout after 30 seconds'
        );

        $this->assertSame('FAILED', $updated->status);
        $this->assertSame('SMTP connection timeout after 30 seconds', $updated->errorMessage);
    }

    // ────────────────────────────────────────────────
    //  RETRY LOGIC
    // ────────────────────────────────────────────────

    /**
     * Increments the attempt count for a notification.
     *
     * @test
     */
    public function testIncrementAttemptCount(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Test notification',
            'bodyText'         => 'Test body.',
            'priority'         => 'normal',
        ]);

        $this->assertSame(0, $notification->attemptCount);

        $updated = $this->model->incrementAttemptCount($notification->id);
        $this->assertSame(1, $updated->attemptCount);

        $updated = $this->model->incrementAttemptCount($notification->id);
        $this->assertSame(2, $updated->attemptCount);

        $this->assertFalse($updated->hasExceededMaxAttempts());
        $this->assertTrue($updated->canRetry());
    }

    /**
     * When attempt count reaches maxAttempts, status transitions to DEAD_LETTER.
     *
     * @test
     */
    public function testDeadLetterAfterMaxAttempts(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Dead letter test',
            'bodyText'         => 'This will eventually fail.',
            'priority'         => 'normal',
        ]);

        // Simulate max retries
        $totalAttempts = $notification->maxAttempts; // default 5

        for ($i = 0; $i < $totalAttempts - 1; $i++) {
            $notification = $this->model->incrementAttemptCount($notification->id);
        }

        $this->assertSame(4, $notification->attemptCount);
        $this->assertFalse($notification->hasExceededMaxAttempts());

        // Final attempt — should exceed max and transition to DEAD_LETTER
        $notification = $this->model->incrementAttemptCount($notification->id);

        $this->assertSame(5, $notification->attemptCount);
        $this->assertTrue($notification->hasExceededMaxAttempts());

        // After exceeding max attempts, mark as dead letter
        $notification = $this->model->markAsDeadLetter($notification->id);

        $this->assertSame('DEAD_LETTER', $notification->status);
        $this->assertTrue($notification->isDeadLetter());
    }

    /**
     * A notification with attempts remaining can be retried.
     *
     * @test
     */
    public function testCanRetry(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Retry test',
            'bodyText'         => 'Can be retried.',
            'priority'         => 'normal',
        ]);

        $this->assertTrue($notification->canRetry());

        // After some attempts but before max
        $notification = $this->model->incrementAttemptCount($notification->id);
        $notification = $this->model->incrementAttemptCount($notification->id);

        $this->assertSame(2, $notification->attemptCount);
        $this->assertTrue($notification->canRetry());
        $this->assertFalse($notification->hasExceededMaxAttempts());
    }

    /**
     * A DEAD_LETTER notification cannot be retried.
     *
     * @test
     */
    public function testCannotRetryDeadLetter(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Cannot retry test',
            'bodyText'         => 'Should be dead.',
            'priority'         => 'normal',
        ]);

        // Manually set to dead letter
        $notification = $this->model->markAsDeadLetter($notification->id);

        $this->assertSame('DEAD_LETTER', $notification->status);
        $this->assertTrue($notification->isDeadLetter());
        $this->assertFalse($notification->canRetry());
    }

    // ────────────────────────────────────────────────
    //  MODEL-LEVEL canRetry
    // ────────────────────────────────────────────────

    /**
     * The model's canRetry method returns true for a PENDING notification.
     *
     * @test
     */
    public function testModelCanRetryPending(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Model can retry',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);

        $this->assertTrue($this->model->canRetry($notification->id));
    }

    /**
     * The model's canRetry method returns false for a SENT notification.
     *
     * @test
     */
    public function testModelCanRetrySent(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Already sent',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);

        $this->model->markAsSent($notification->id, 'prov-msg-123');

        $this->assertFalse($this->model->canRetry($notification->id));
    }

    /**
     * The model's canRetry method returns false for a non-existing notification.
     *
     * @test
     */
    public function testModelCanRetryNonExisting(): void
    {
        $this->assertFalse($this->model->canRetry('00000000-0000-0000-0000-000000000000'));
    }

    // ────────────────────────────────────────────────
    //  MARK AS SENDING — LAST ATTEMPT TIMESTAMP
    // ────────────────────────────────────────────────

    /**
     * Marking as SENDING records the lastAttemptAt timestamp.
     *
     * @test
     */
    public function testMarkAsSendingSetsLastAttemptAt(): void
    {
        $notification = $this->model->createNotification([
            'recipientEmail'  => 'test@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Sending timestamp test',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);

        $this->assertNull($notification->lastAttemptAt);

        $updated = $this->model->markAsSending($notification->id);

        $this->assertSame('SENDING', $updated->status);
        $this->assertNotNull($updated->lastAttemptAt);
    }

    // ────────────────────────────────────────────────
    //  FIND PENDING — WITH DATA
    // ────────────────────────────────────────────────

    /**
     * findPending returns only notifications with PENDING status when
     * mixed-status records exist.
     *
     * @test
     */
    public function testFindPendingWithMixedStatuses(): void
    {
        // Create PENDING
        $this->model->createNotification([
            'recipientEmail'  => 'pending1@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Pending 1',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);

        $this->model->createNotification([
            'recipientEmail'  => 'pending2@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Pending 2',
            'bodyText'         => 'Test.',
            'priority'         => 'high',
        ]);

        // Create SENT (should not appear)
        $sent = $this->model->createNotification([
            'recipientEmail'  => 'sent@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Sent',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);
        $this->model->markAsSent($sent->id, 'prov-1');

        // Create FAILED (should not appear)
        $failed = $this->model->createNotification([
            'recipientEmail'  => 'failed@example.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Failed',
            'bodyText'         => 'Test.',
            'priority'         => 'normal',
        ]);
        $this->model->markAsFailed($failed->id, 'SMTP error');

        $pending = $this->model->findPending();

        $this->assertIsArray($pending);
        $this->assertCount(2, $pending);

        foreach ($pending as $n) {
            $this->assertSame('PENDING', $n->status);
        }
    }
}
