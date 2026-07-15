<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * TransfersWebTest — basic HTTP smoke tests for web transfer pages.
 *
 * Sessions are handled by the SHIELD session filter which
 * redirects unauthenticated users to /login (302).
 *
 * @coversNothing
 * @internal
 */
final class TransfersWebTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // ── New Transfer page ──────────────────────────────────────────

    public function testNewTransferPageReturns200OrRedirect(): void
    {
        $result = $this->get('/transfers/new');
        $this->assertContains($result->response()->getStatusCode(), [200, 302]);
    }

    // ── Inbox ──────────────────────────────────────────────────────

    public function testInboxPageReturnsRedirect(): void
    {
        $result = $this->get('/inbox');
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    // ── Outbox ─────────────────────────────────────────────────────

    public function testOutboxPageReturnsRedirect(): void
    {
        $result = $this->get('/outbox');
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    // ── Accept / Reject ────────────────────────────────────────────

    public function testAcceptWithoutSessionRedirects(): void
    {
        $result = $this->post('/transfers/test-uuid/accept');
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    public function testRejectWithoutSessionRedirects(): void
    {
        $result = $this->post('/transfers/test-uuid/reject');
        $this->assertSame(302, $result->response()->getStatusCode());
    }
}
