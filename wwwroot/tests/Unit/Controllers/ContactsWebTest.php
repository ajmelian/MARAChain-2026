<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * ContactsWebTest — basic HTTP smoke tests for web contacts page.
 *
 * @coversNothing
 * @internal
 */
final class ContactsWebTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testContactsPageRedirectsWithoutSession(): void
    {
        $result = $this->get('/web/contacts');
        $this->assertSame(302, $result->response()->getStatusCode());
    }

    public function testContactsPostRedirectsWithoutSession(): void
    {
        $result = $this->post('/web/contacts', [
            'contactType'  => 'physical_person',
            'firstName'    => 'Test',
            'emailPrimary' => 'test@test.com',
        ]);
        $this->assertSame(302, $result->response()->getStatusCode());
    }
}
