<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AuthControllerTest — SHIELD authentication smoke tests on MySQL.
 *
 * Uses DatabaseTestTrait with $refresh = false to keep SHIELD tables
 * intact between tests.
 *
 * @coversNothing
 * @internal
 */
final class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = false;
    protected $namespace = 'App';

    public function testRegisterPageReturns200(): void
    {
        $result = $this->get('/register');
        $this->assertSame(200, $result->response()->getStatusCode());
    }

    public function testRegisterPageContainsForm(): void
    {
        $result = $this->get('/register');
        $this->assertStringContainsString('<form', $result->response()->getBody());
    }

    public function testRegisterPostWithoutEmailRedirects(): void
    {
        $result = $this->post('/register', []);
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    public function testLoginPostWithInvalidCredentialsRedirects(): void
    {
        $result = $this->post('/login', [
            'email'    => 'nonexistent@test.com',
            'password' => 'wrong',
        ]);
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    public function testLogoutRedirects(): void
    {
        $result = $this->get('/logout');
        $this->assertSame(302, $result->response()->getStatusCode());
    }
}
