<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Entities\Contact;
use App\Models\ContactModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ContactsController — Controlador web para gestion de contactos (agenda).
 *
 * Proporciona las vistas de listado de contactos y las operaciones CRUD
 * via formularios HTML con redireccion.
 *
 * En fase MVP el listado usa datos mock; las operaciones de escritura
 * (store/update/delete) interactuan con el ContactModel real.
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.2.0
 */
class ContactsController extends BaseWebController
{
    private ContactModel $contactModel;

    /**
     * Constructor.
     *
     * @since 1.2.0
     */
    public function __construct()
    {
        $this->contactModel = model(ContactModel::class);
    }

    /**
     * GET /contacts — Muestra el listado de contactos.
     *
     * En MVP devuelve datos mock; en produccion consultara al modelo
     * filtrando por el usuario autenticado.
     *
     * @return string HTML renderizado con la tabla de contactos
     *
     * @since 1.2.0
     */
    public function index(): string
    {
        // ── Mock data para MVP ──
        $mockData = [
            [
                'id'             => 'c1c1c1c1-c1c1-41c1-a1c1-c1c1c1c1c1c1',
                'ownerId'        => '00000000-0000-4000-a000-000000000001',
                'contactType'    => 'physical_person',
                'firstName'      => 'Maria',
                'lastName'       => 'Garcia',
                'legalName'      => null,
                'attentionOf'    => null,
                'emailPrimary'   => 'maria@example.com',
                'phone'          => '+34600123456',
                'country'        => 'ES',
                'postalCode'     => '28001',
                'identityStatus' => 'verified',
                'linkedUserId'   => null,
            ],
            [
                'id'             => 'c2c2c2c2-c2c2-42c2-a2c2-c2c2c2c2c2c2',
                'ownerId'        => '00000000-0000-4000-a000-000000000001',
                'contactType'    => 'legal_entity',
                'firstName'      => null,
                'lastName'       => null,
                'legalName'      => 'Empresas Unidas S.L.',
                'attentionOf'    => 'Juan Martinez',
                'emailPrimary'   => 'juan@empresasunidas.com',
                'phone'          => null,
                'country'        => 'ES',
                'postalCode'     => '08001',
                'identityStatus' => 'pending',
                'linkedUserId'   => null,
            ],
        ];

        // Convertir arrays a entidades Contact
        $contacts = array_map(
            static fn (array $data): Contact => new Contact($data),
            $mockData
        );

        return $this->render('contacts/index', [
            'title'    => 'Contactos',
            'contacts' => $contacts,
        ]);
    }

    /**
     * POST /contacts — Crea un nuevo contacto.
     *
     * Lee los datos del formulario y los pasa al ContactModel::createContact().
     * Redirige al listado de contactos al finalizar.
     *
     * @return ResponseInterface Redireccion a /contacts
     *
     * @since 1.2.0
     */
    public function store(): ResponseInterface
    {
        $data = [
            'ownerId'     => $this->mockUser()['id'],
            'contactType' => $this->request->getPost('contact_type') ?? 'physical_person',
            'firstName'   => $this->request->getPost('first_name'),
            'lastName'    => $this->request->getPost('last_name'),
            'legalName'   => $this->request->getPost('legal_name'),
            'attentionOf' => $this->request->getPost('attention_of'),
            'emailPrimary'=> $this->request->getPost('email_primary') ?? '',
            'phone'       => $this->request->getPost('phone'),
            'country'     => $this->request->getPost('country') ?? 'ES',
            'postalCode'  => $this->request->getPost('postal_code'),
        ];

        try {
            $this->contactModel->createContact($data);
        } catch (\InvalidArgumentException $e) {
            // En MVP simplemente redirigimos; en produccion se mostraria un mensaje flash
            log_message('error', 'Contact creation failed: ' . $e->getMessage());
        }

        return redirect()->to('/contacts');
    }

    /**
     * PUT /contacts/{id} — Actualiza un contacto existente.
     *
     * @param string $id UUID del contacto a actualizar
     *
     * @return ResponseInterface Redireccion a /contacts
     *
     * @since 1.2.0
     */
    public function update(string $id): ResponseInterface
    {
        $allowedFields = [
            'contact_type' => 'contactType',
            'first_name'   => 'firstName',
            'last_name'    => 'lastName',
            'legal_name'   => 'legalName',
            'attention_of' => 'attentionOf',
            'email_primary'=> 'emailPrimary',
            'phone'        => 'phone',
            'country'      => 'country',
            'postal_code'  => 'postalCode',
        ];

        $updateData = [];

        foreach ($allowedFields as $field => $property) {
            $value = $this->request->getPost($field);
            if ($value !== null) {
                $updateData[$property] = $value;
            }
        }

        if (! empty($updateData)) {
            $this->contactModel->update($id, $updateData);
        }

        return redirect()->to('/contacts');
    }

    /**
     * DELETE /contacts/{id} — Elimina un contacto.
     *
     * @param string $id UUID del contacto a eliminar
     *
     * @return ResponseInterface Redireccion a /contacts
     *
     * @since 1.2.0
     */
    public function delete(string $id): ResponseInterface
    {
        $this->contactModel->delete($id);

        return redirect()->to('/contacts');
    }
}
