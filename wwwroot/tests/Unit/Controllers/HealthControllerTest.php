<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for HealthController.
 *
 * Tests the GET /health endpoint which returns a JSON health-check
 * report used by deploy scripts and monitoring systems.
 *
 * @coversNothing (integration test)
 *
 * @since   1.4.0
 * @author  Aythami
 *
 * @internal
 */
final class HealthControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // ──────────────────────────────────────────────────────────────
    // GET /health
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /health returns HTTP 200 when the system is healthy.
     *
     * @test
     */
    public function testHealthReturns200(): void
    {
        $result = $this->get('/health');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * GET /health returns a JSON object with status and checks keys.
     *
     * @test
     */
    public function testHealthReturnsJson(): void
    {
        $result = $this->get('/health');
        $body   = json_decode($result->response()->getBody(), true);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('checks', $body);
    }

    /**
     * GET /health status field is a valid health-state string.
     *
     * Accepts 'healthy' (all checks pass) or 'degraded'
     * (one or more checks failed).
     *
     * @test
     */
    public function testHealthStatusIsString(): void
    {
        $result = $this->get('/health');
        $body   = json_decode($result->response()->getBody(), true);

        $this->assertContains($body['status'], ['healthy', 'degraded']);
    }

    /**
     * GET /health checks array includes a database connectivity key.
     *
     * @test
     */
    public function testHealthHasDatabaseCheck(): void
    {
        $result = $this->get('/health');
        $body   = json_decode($result->response()->getBody(), true);

        $this->assertArrayHasKey('database', $body['checks']);
    }

    /**
     * GET /health checks array includes the application version.
     *
     * @test
     */
    public function testHealthHasVersion(): void
    {
        $result = $this->get('/health');
        $body   = json_decode($result->response()->getBody(), true);

        $this->assertArrayHasKey('version', $body['checks']);
    }
}
