<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\NotificationModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for NotificationController.
 *
 * <p>Tests listing and retrieval of outbound notification records.
 * Notifications are produced by the system for various events
 * (transfers, auth, device changes) and sent to recipients.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class NotificationControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    /**
     * Refresh the database before each test.
     *
     * @var bool
     */
    protected $refresh = true;

    /**
     * The namespace for migration discovery.
     *
     * @var string|null
     */
    protected $namespace = 'App';

    /**
     * Authenticated user UUID.
     *
     * @var string
     */
    private string $testUserId;

    /**
     * Pre-created notification UUID for testShow.
     *
     * @var string
     */
    private string $testNotificationId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a user and a notification for index/show tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $userModel         = new UserModel();
        $notificationModel = new NotificationModel();

        // Create the authenticated user
        $user = $userModel->create([
            'firstName'    => 'Notify',
            'lastName'     => 'User',
            'email'        => 'notify.' . bin2hex(random_bytes(4)) . '@test.com',
            'identityType' => 'physical',
        ]);
        $this->testUserId = $user->id;

        // Create a notification for show tests
        $notification = $notificationModel->createNotification([
            'recipientUserId'  => $this->testUserId,
            'recipientEmail'   => 'notify.' . bin2hex(random_bytes(4)) . '@test.com',
            'notificationType' => 'transfer_available',
            'subject'          => 'Test Notification',
            'bodyText'         => 'This is a test notification.',
        ]);
        $this->testNotificationId = $notification->id;

        $this->withSession([
            'user_id' => $this->testUserId,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /notifications
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing notifications returns HTTP 200 with a JSON array of the
     * authenticated user's notifications.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/notifications');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Listing notifications filtered by type returns HTTP 200.
     *
     * @test
     */
    public function testIndexFilteredByType(): void
    {
        $result = $this->call('get', '/notifications?notification_type=transfer_available');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Listing notifications filtered by status returns HTTP 200.
     *
     * @test
     */
    public function testIndexFilteredByStatus(): void
    {
        $result = $this->call('get', '/notifications?status=SENT');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /notifications/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single notification by UUID returns HTTP 200 with
     * the notification JSON including subject and body.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/notifications/' . $this->testNotificationId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent notification returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownNotification(): void
    {
        $result = $this->get('/notifications/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
