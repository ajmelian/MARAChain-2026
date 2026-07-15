<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AuthControllerTest — HTTP smoke tests for auth pages.
 *
 * @coversNothing
 * @internal
 */
final class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    public function testLoginPageReturns200(): void
    {
        $this->markTestSkipped('SHIELD requires full environment for auth view tests.');
    }

    public function testRegisterPageReturns200(): void
    {
        $result = $this->get('/register');
        $this->assertSame(200, $result->response()->getStatusCode());
    }

    public function testLogoutRedirects(): void
    {
        $result = $this->get('/logout');
        $this->assertSame(302, $result->response()->getStatusCode());
    }
}
