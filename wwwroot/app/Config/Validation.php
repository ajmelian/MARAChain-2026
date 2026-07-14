<?php

declare(strict_types=1);

namespace Config;

use App\Validation\CustomRules;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    /**
     * Stores the classes that contain the rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        CustomRules::class,
    ];

    /**
     * Specifies the views that are used to display the errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // ─────────────────────────────────────────────────────────────────────
    //  Validation Groups
    // ─────────────────────────────────────────────────────────────────────

    /**
     * User validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $user = [
        'email' => [
            'label'  => 'Email',
            'rules'  => 'required|valid_email|max_length[254]',
            'errors' => [
                'required'    => 'El email es obligatorio.',
                'valid_email' => 'El email no tiene un formato valido.',
                'max_length'  => 'El email no puede superar los 254 caracteres.',
            ],
        ],
        'identity_type' => [
            'label'  => 'Tipo de identidad',
            'rules'  => 'required|in_list[physical,legal]',
            'errors' => [
                'required' => 'El tipo de identidad es obligatorio.',
                'in_list'  => 'El tipo debe ser physical o legal.',
            ],
        ],
        'first_name' => [
            'label'  => 'Nombre',
            'rules'  => 'required|max_length[100]',
            'errors' => [
                'required'   => 'El nombre es obligatorio.',
                'max_length' => 'El nombre no puede superar 100 caracteres.',
            ],
        ],
        'last_name' => [
            'label'  => 'Apellidos',
            'rules'  => 'permit_empty|max_length[150]',
        ],
        'legal_name' => [
            'label'  => 'Razon social',
            'rules'  => 'permit_empty|max_length[200]',
        ],
        'status' => [
            'label'  => 'Estado',
            'rules'  => 'permit_empty|in_list[active,inactive,suspended,blocked]',
        ],
        'guarantee_level' => [
            'label'  => 'Nivel de garantia',
            'rules'  => 'permit_empty|in_list[low,substantial,high]',
        ],
        'phone' => [
            'label'  => 'Telefono',
            'rules'  => 'permit_empty|valid_phone_e164',
        ],
        'tax_id_encrypted' => [
            'label'  => 'NIF/NIE/CIF',
            'rules'  => 'permit_empty|valid_tax_id',
        ],
    ];

    /**
     * Device validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $device = [
        'device_name' => [
            'label'  => 'Nombre del dispositivo',
            'rules'  => 'required|max_length[100]',
            'errors' => ['required' => 'El nombre del dispositivo es obligatorio.'],
        ],
        'device_type' => [
            'label'  => 'Tipo de dispositivo',
            'rules'  => 'required|in_list[desktop,laptop,tablet,mobile,other]',
            'errors' => ['required' => 'El tipo de dispositivo es obligatorio.'],
        ],
        'public_key_fingerprint' => [
            'label'  => 'Huella de clave publica',
            'rules'  => 'required|exact_length[64]',
            'errors' => ['required' => 'La huella de clave publica es obligatoria.'],
        ],
        'public_key_algorithm' => [
            'label'  => 'Algoritmo de clave',
            'rules'  => 'required|max_length[20]',
        ],
    ];

    /**
     * Document validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $document = [
        'title' => [
            'label'  => 'Titulo',
            'rules'  => 'required|max_length[255]',
            'errors' => ['required' => 'El titulo es obligatorio.'],
        ],
        'mime_type' => [
            'label'  => 'Tipo MIME',
            'rules'  => 'required|in_list[application/pdf]',
            'errors' => ['required' => 'El tipo MIME es obligatorio.'],
        ],
        'file_size' => [
            'label'  => 'Tamano del fichero',
            'rules'  => 'required|greater_than[0]',
            'errors' => ['required' => 'El tamano del fichero es obligatorio.'],
        ],
        'file_hash_sha256' => [
            'label'  => 'Hash SHA-256',
            'rules'  => 'required|exact_length[64]',
        ],
        'owner_id' => [
            'label'  => 'Propietario',
            'rules'  => 'required',
        ],
    ];

    /**
     * Document transfer validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $transfer = [
        'security_level' => [
            'label'  => 'Nivel de seguridad',
            'rules'  => 'required|in_list[standard,signed,signed_sealed]',
        ],
        'idempotency_key' => [
            'label'  => 'Clave de idempotencia',
            'rules'  => 'required|exact_length[64]',
            'errors' => ['required' => 'La clave de idempotencia es obligatoria.'],
        ],
        'document_id' => [
            'label'  => 'Documento',
            'rules'  => 'required',
        ],
        'sender_id' => [
            'label'  => 'Remitente',
            'rules'  => 'required',
        ],
        'recipient_id' => [
            'label'  => 'Destinatario',
            'rules'  => 'required',
        ],
    ];

    /**
     * Signature request validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $signature = [
        'signature_intent' => [
            'label'  => 'Intencion de firma',
            'rules'  => 'required|max_length[50]',
        ],
        'signature_provider' => [
            'label'  => 'Proveedor de firma',
            'rules'  => 'required|max_length[50]',
        ],
        'digest_algorithm' => [
            'label'  => 'Algoritmo de digest',
            'rules'  => 'required|max_length[20]',
        ],
        'manifest_hash' => [
            'label'  => 'Hash del manifiesto',
            'rules'  => 'required|exact_length[64]',
        ],
        'nonce' => [
            'label'  => 'Nonce',
            'rules'  => 'required|exact_length[64]',
        ],
        'document_id' => [
            'label'  => 'Documento',
            'rules'  => 'required',
        ],
        'user_id' => [
            'label'  => 'Usuario firmante',
            'rules'  => 'required',
        ],
    ];

    /**
     * Evidence validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $evidence = [
        'event_id' => [
            'label'  => 'ID de evento',
            'rules'  => 'required|exact_length[36]',
        ],
        'event_type' => [
            'label'  => 'Tipo de evento',
            'rules'  => 'required|max_length[100]',
        ],
        'payload_json' => [
            'label'  => 'Payload JSON',
            'rules'  => 'required',
        ],
        'payload_hash' => [
            'label'  => 'Hash del payload',
            'rules'  => 'required|exact_length[64]',
        ],
        'aggregate_type' => [
            'label'  => 'Tipo de agregado',
            'rules'  => 'required|max_length[50]',
        ],
        'aggregate_id' => [
            'label'  => 'ID del agregado',
            'rules'  => 'required',
        ],
    ];

    /**
     * Ledger block validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $ledger = [
        'block_number' => [
            'label'  => 'Numero de bloque',
            'rules'  => 'required|greater_than[0]',
        ],
        'merkle_root' => [
            'label'  => 'Raiz Merkle',
            'rules'  => 'required|exact_length[64]',
        ],
        'block_hash' => [
            'label'  => 'Hash del bloque',
            'rules'  => 'required|exact_length[64]',
        ],
        'block_signature' => [
            'label'  => 'Firma del bloque',
            'rules'  => 'required',
        ],
        'signing_key_fingerprint' => [
            'label'  => 'Huella de clave de firma',
            'rules'  => 'required|exact_length[64]',
        ],
        'events_json' => [
            'label'  => 'Eventos JSON',
            'rules'  => 'required',
        ],
    ];

    /**
     * Contact validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $contact = [
        'contact_type' => [
            'label'  => 'Tipo de contacto',
            'rules'  => 'required|in_list[physical_person,legal_entity]',
            'errors' => ['required' => 'El tipo de contacto es obligatorio.'],
        ],
        'first_name' => [
            'label'  => 'Nombre',
            'rules'  => 'required_with[contact_type,physical_person]|max_length[100]',
        ],
        'legal_name' => [
            'label'  => 'Razon social',
            'rules'  => 'required_with[contact_type,legal_entity]|max_length[200]',
        ],
        'attention_of' => [
            'label'  => 'A la atencion de',
            'rules'  => 'required_with[contact_type,legal_entity]|max_length[200]',
        ],
        'email_primary' => [
            'label'  => 'Email principal',
            'rules'  => 'required|valid_email|max_length[254]',
            'errors' => ['required' => 'El email principal es obligatorio.'],
        ],
        'phone' => [
            'label'  => 'Telefono',
            'rules'  => 'permit_empty|valid_phone_e164',
        ],
        'tax_id_encrypted' => [
            'label'  => 'NIF/NIE/CIF',
            'rules'  => 'permit_empty|valid_tax_id',
        ],
        'country' => [
            'label'  => 'Pais',
            'rules'  => 'exact_length[2]',
        ],
        'owner_id' => [
            'label'  => 'Propietario',
            'rules'  => 'required',
        ],
    ];

    /**
     * Notification validation rules.
     *
     * @var array<string, array<int, array<string, string>|string>>
     */
    public array $notification = [
        'recipient_email' => [
            'label'  => 'Email del destinatario',
            'rules'  => 'required|valid_email|max_length[254]',
            'errors' => ['required' => 'El email del destinatario es obligatorio.'],
        ],
        'notification_type' => [
            'label'  => 'Tipo de notificacion',
            'rules'  => 'required|in_list[auth_success,auth_failed,totp_reset,device_added,transfer_available,transfer_accessed,transfer_downloaded,transfer_revoked,transfer_expired,invitation,session_revoked,account_blocked,admin_action]',
        ],
        'subject' => [
            'label'  => 'Asunto',
            'rules'  => 'required|max_length[255]',
        ],
        'status' => [
            'label'  => 'Estado',
            'rules'  => 'in_list[PENDING,SENDING,SENT,FAILED,DEAD_LETTER]',
        ],
        'priority' => [
            'label'  => 'Prioridad',
            'rules'  => 'in_list[low,normal,high,critical]',
        ],
    ];
}
