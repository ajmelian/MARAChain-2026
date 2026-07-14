<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Contact;
use App\Models\ContactModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;

/**
 * Unit tests for ContactModel.
 *
 * <p>RED phase: ContactModel does not exist yet.
 * These tests define the expected contract and MUST FAIL until
 * the model is implemented.</p>
 *
 * <p>Contacts may be physical persons or legal entities. Contact data
 * does not imply document authorization. Identity status transitions
 * through pending -> invited -> verified or rejected.</p>
 *
 * @coversNothing (model does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 */
final class ContactModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /** @var bool */
    protected $refresh = true;

    /** @var string */
    protected $namespace = 'App';

    private ContactModel $model;
    private UserModel $userModel;
    private string $ownerUserId;
    private string $linkedUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel = new UserModel();
        $this->model = new ContactModel();

        $user = $this->userModel->create([
            'firstName'    => 'Owner',
            'lastName'     => 'User',
            'email'        => 'owner' . bin2hex(random_bytes(4)) . '@example.com',
            'identityType' => 'physical',
        ]);
        $this->ownerUserId = $user->id;

        $linked = $this->userModel->create([
            'firstName'    => 'Linked',
            'lastName'     => 'User',
            'email'        => 'linked' . bin2hex(random_bytes(4)) . '@example.com',
            'identityType' => 'physical',
        ]);
        $this->linkedUserId = $linked->id;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ────────────────────────────────────────────────
    //  CREATE — Physical Person
    // ────────────────────────────────────────────────

    /**
     * Creates a physical person contact with all required fields.
     *
     * @test
     */
    public function testCreatePhysicalPersonContact(): void
    {
        $data = [
            'ownerId'        => $this->ownerUserId,
            'contactType'    => 'physical_person',
            'firstName'      => 'Maria',
            'lastName'       => 'Garcia Lopez',
            'emailPrimary'   => 'maria.garcia@example.com',
            'phone'          => '+34600123456',
            'country'        => 'ES',
        ];

        $contact = $this->model->createContact($data);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertNotEmpty($contact->id);
        $this->assertSame('physical_person', $contact->contactType);
        $this->assertSame('Maria', $contact->firstName);
        $this->assertSame('Garcia Lopez', $contact->lastName);
        $this->assertSame('maria.garcia@example.com', $contact->emailPrimary);
        $this->assertSame('+34600123456', $contact->phone);
        $this->assertSame('ES', $contact->country);
        $this->assertSame('pending', $contact->identityStatus);
        $this->assertTrue($contact->isPhysicalPerson());
        $this->assertFalse($contact->isLegalEntity());
        $this->assertTrue($contact->isPending());
    }

    // ────────────────────────────────────────────────
    //  CREATE — Legal Entity
    // ────────────────────────────────────────────────

    /**
     * Creates a legal entity contact with all required fields.
     *
     * @test
     */
    public function testCreateLegalEntityContact(): void
    {
        $data = [
            'ownerId'      => $this->ownerUserId,
            'contactType'  => 'legal_entity',
            'legalName'    => 'Empresas Unidas S.L.',
            'attentionOf'  => 'Juan Martinez',
            'emailPrimary' => 'juan.martinez@empresasunidas.com',
        ];

        $contact = $this->model->createContact($data);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertNotEmpty($contact->id);
        $this->assertSame('legal_entity', $contact->contactType);
        $this->assertSame('Empresas Unidas S.L.', $contact->legalName);
        $this->assertSame('Juan Martinez', $contact->attentionOf);
        $this->assertSame('juan.martinez@empresasunidas.com', $contact->emailPrimary);
        $this->assertTrue($contact->isLegalEntity());
        $this->assertFalse($contact->isPhysicalPerson());
    }

    // ────────────────────────────────────────────────
    //  CREATE — Validation
    // ────────────────────────────────────────────────

    /**
     * Creating a contact without an ownerId must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutOwnerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ownerId');

        $data = [
            // ownerId omitted intentionally
            'contactType'  => 'physical_person',
            'firstName'    => 'Pedro',
            'lastName'     => 'Sanchez',
            'emailPrimary' => 'pedro.sanchez@example.com',
        ];

        $this->model->createContact($data);
    }

    /**
     * Creating a contact without an email must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('emailPrimary');

        $data = [
            'ownerId'     => $this->ownerUserId,
            'contactType' => 'physical_person',
            'firstName'   => 'Ana',
            'lastName'    => 'Lopez',
            // emailPrimary omitted intentionally
        ];

        $this->model->createContact($data);
    }

    // ────────────────────────────────────────────────
    //  FIND
    // ────────────────────────────────────────────────

    /**
     * Finds all contacts belonging to an owner user.
     *
     * @test
     */
    public function testFindByOwnerId(): void
    {
        $ownerId = $this->ownerUserId;

        $results = $this->model->findByOwnerId($ownerId);

        $this->assertIsArray($results);
    }

    /**
     * Finds a contact by its primary email address.
     *
     * @test
     */
    public function testFindByEmail(): void
    {
        $email = 'contact' . bin2hex(random_bytes(4)) . '@example.com';
        $this->model->createContact([
            'ownerId'      => $this->ownerUserId,
            'contactType'  => 'physical_person',
            'firstName'    => 'EmailFind',
            'emailPrimary' => $email,
        ]);

        $result = $this->model->findByEmail($email);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Contact::class, $result);
        $this->assertSame($email, $result->emailPrimary);
    }

    /**
     * Finds contacts filtered by identity status.
     *
     * @test
     */
    public function testFindByStatus(): void
    {
        $results = $this->model->findByStatus('pending');

        $this->assertIsArray($results);

        foreach ($results as $contact) {
            $this->assertInstanceOf(Contact::class, $contact);
            $this->assertSame('pending', $contact->identityStatus);
        }
    }

    // ────────────────────────────────────────────────
    //  STATE TRANSITIONS — Identity Status
    // ────────────────────────────────────────────────

    /**
     * Updates the identity status from pending to invited to verified.
     *
     * @test
     */
    public function testUpdateIdentityStatus(): void
    {
        // Create contact with default pending status
        $contact = $this->model->createContact([
            'ownerId'      => $this->ownerUserId,
            'contactType'  => 'physical_person',
            'firstName'    => 'Carlos',
            'lastName'     => 'Diaz',
            'emailPrimary' => 'carlos.diaz@example.com',
        ]);

        $this->assertSame('pending', $contact->identityStatus);

        // Transition: pending -> invited
        $contact = $this->model->updateIdentityStatus($contact->id, 'invited');
        $this->assertSame('invited', $contact->identityStatus);

        // Transition: invited -> verified
        $contact = $this->model->updateIdentityStatus($contact->id, 'verified');
        $this->assertSame('verified', $contact->identityStatus);
        $this->assertTrue($contact->isVerified());
    }

    /**
     * Links a contact to a registered user when identity is verified.
     *
     * @test
     */
    public function testLinkToUser(): void
    {
        $contact = $this->model->createContact([
            'ownerId'      => $this->ownerUserId,
            'contactType'  => 'physical_person',
            'firstName'    => 'Laura',
            'lastName'     => 'Fernandez',
            'emailPrimary' => 'laura.fernandez@example.com',
        ]);

        $linkedUserId = $this->linkedUserId;
        $publicKeyFp  = 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';

        // Verify and link
        $contact = $this->model->verifyAndLink($contact->id, $linkedUserId, $publicKeyFp);

        $this->assertSame('verified', $contact->identityStatus);
        $this->assertSame($linkedUserId, $contact->linkedUserId);
        $this->assertSame($publicKeyFp, $contact->publicKeyFingerprint);
        $this->assertTrue($contact->isVerified());
        $this->assertTrue($contact->isLinked());
    }

    /**
     * Rejecting identity verification sets status to rejected.
     *
     * @test
     */
    public function testRejectIdentity(): void
    {
        $contact = $this->model->createContact([
            'ownerId'      => $this->ownerUserId,
            'contactType'  => 'physical_person',
            'firstName'    => 'Miguel',
            'lastName'     => 'Torres',
            'emailPrimary' => 'miguel.torres@example.com',
        ]);

        $this->assertSame('pending', $contact->identityStatus);

        $contact = $this->model->updateIdentityStatus($contact->id, 'rejected');

        $this->assertSame('rejected', $contact->identityStatus);
        $this->assertFalse($contact->isVerified());
        $this->assertFalse($contact->isPending());
    }
}
