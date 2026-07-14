<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Entities\DocumentTransfer;
use App\Models\DocumentTransferModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TransfersController — Controlador web para gestion de transferencias de documentos.
 *
 * Gestiona las vistas de bandeja de entrada (inbox), documentos enviados (outbox),
 * creacion de nuevas transferencias y detalle individual.
 *
 * En fase MVP usa datos mock; en produccion se conectara al DocumentTransferModel.
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.2.0
 */
class TransfersController extends BaseWebController
{
    private DocumentTransferModel $transferModel;

    /**
     * Constructor.
     *
     * @since 1.2.0
     */
    public function __construct()
    {
        $this->transferModel = model(DocumentTransferModel::class);
    }

    /**
     * GET /transfers/inbox — Muestra la bandeja de entrada (transferencias recibidas).
     *
     * Lee el parametro opcional ?status= para filtrar por estado.
     * En MVP devuelve datos mock; en produccion consultara al modelo.
     *
     * @return string HTML renderizado con la vista de inbox
     *
     * @since 1.2.0
     */
    public function inbox(): string
    {
        $statusFilter = $this->request->getGet('status');
        $userId       = $this->getAuthenticatedUserId();

        if ($userId !== null) {
            $transfers = $statusFilter
                ? array_filter(
                    $this->transferModel->findByRecipientId($userId),
                    static fn ($t) => $t->status === $statusFilter
                )
                : $this->transferModel->findByRecipientId($userId);
        } else {
            $transfers = [];
        }

        return $this->render('transfers/inbox', [
            'title'        => 'Bandeja de entrada',
            'current'      => 'inbox',
            'transfers'    => $transfers,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * GET /transfers/outbox — Muestra los documentos enviados por el usuario.
     *
     * @return string HTML renderizado con la vista de outbox
     *
     * @since 1.2.0
     */
    public function outbox(): string
    {
        $statusFilter = $this->request->getGet('status');
        $userId       = $this->getAuthenticatedUserId();

        if ($userId !== null) {
            $transfers = $statusFilter
                ? array_filter(
                    $this->transferModel->findBySenderId($userId),
                    static fn ($t) => $t->status === $statusFilter
                )
                : $this->transferModel->findBySenderId($userId);
        } else {
            $transfers = [];
        }

        return $this->render('transfers/outbox', [
            'title'        => 'Documentos enviados',
            'current'      => 'outbox',
            'transfers'    => $transfers,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * GET /transfers/new — Muestra el formulario de nueva transferencia.
     *
     * @return string HTML renderizado con el formulario de creacion
     *
     * @since 1.2.0
     */
    public function new(): string
    {
        // Lista de paises ISO 3166-1 alpha-2 para el formulario
        $countries = [
            'ES' => 'Espa&ntilde;a',
            'AD' => 'Andorra',
            'AR' => 'Argentina',
            'BO' => 'Bolivia',
            'BR' => 'Brasil',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'CR' => 'Costa Rica',
            'CU' => 'Cuba',
            'DO' => 'Rep&uacute;blica Dominicana',
            'EC' => 'Ecuador',
            'SV' => 'El Salvador',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'MX' => 'M&eacute;xico',
            'NI' => 'Nicaragua',
            'PA' => 'Panam&aacute;',
            'PY' => 'Paraguay',
            'PE' => 'Per&uacute;',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'US' => 'Estados Unidos',
            'UY' => 'Uruguay',
            'VE' => 'Venezuela',
        ];

        return $this->render('transfers/create', [
            'title'    => 'Nuevo env&iacute;o',
            'current'  => 'new',
            'countries' => $countries,
            'contacts'  => [],
        ]);
    }

    /**
     * GET /transfers/{id} — Muestra el detalle de una transferencia.
     *
     * Renderiza HTML inline con la informacion de la transferencia
     * y un enlace de vuelta.
     *
     * @param string $id UUID de la transferencia
     *
     * @return string HTML renderizado con el detalle
     *
     * @since 1.2.0
     */
    public function detail(string $id): string
    {
        // Buscar en mock data para MVP
        $mockData = [
            '880e8400-e29b-41d4-a716-446655440003' => [
                'id'          => '880e8400-e29b-41d4-a716-446655440003',
                'senderId'    => '550e8400-e29b-41d4-a716-446655440000',
                'recipientId' => '00000000-0000-4000-a000-000000000001',
                'documentId'  => '770e8400-e29b-41d4-a716-446655440002',
                'status'      => 'AVAILABLE',
                'createdAt'   => '2026-07-13 09:00:00',
            ],
            '881e8400-e29b-41d4-a716-446655440004' => [
                'id'          => '881e8400-e29b-41d4-a716-446655440004',
                'senderId'    => '00000000-0000-4000-a000-000000000001',
                'recipientId' => '660e8400-e29b-41d4-a716-446655440005',
                'documentId'  => '771e8400-e29b-41d4-a716-446655440006',
                'status'      => 'SENT',
                'createdAt'   => '2026-07-12 15:30:00',
            ],
        ];

        $data = $mockData[$id] ?? null;

        if ($data === null) {
            return $this->render('transfers/not_found', [
                'title'   => 'Transferencia no encontrada',
                'current' => 'inbox',
            ]);
        }

        $transfer = new DocumentTransfer($data);

        $statusLabels = [
            'PENDING_RECIPIENT' => 'Pendiente',
            'READY'             => 'Listo',
            'SENDING'           => 'Enviando',
            'SENT'              => 'Enviado',
            'AVAILABLE'         => 'Disponible',
            'ACCESSED'          => 'Accedido',
            'DOWNLOADED'        => 'Descargado',
            'ACCEPTED'          => 'Aceptado',
            'REJECTED'          => 'Rechazado',
            'EXPIRED'           => 'Expirado',
            'REVOKED'           => 'Revocado',
            'FAILED'            => 'Fallido',
        ];

        $statusLabel = $statusLabels[$transfer->status] ?? $transfer->status;

        $badgeMap = [
            'AVAILABLE' => 'badge-info',
            'ACCESSED'  => 'badge-primary',
            'DOWNLOADED'=> 'badge-success',
            'ACCEPTED'  => 'badge-success',
            'REJECTED'  => 'badge-danger',
            'EXPIRED'   => 'badge-warning',
            'REVOKED'   => 'badge-dark',
            'FAILED'    => 'badge-danger',
        ];
        $badgeClass = $badgeMap[$transfer->status] ?? 'badge-secondary';

        return $this->render('transfers/detail', [
            'title'       => 'Transferencia ' . esc($id),
            'current'     => 'inbox',
            'transfer'    => $data,
            'statusLabel' => $statusLabel,
            'badgeClass'  => $badgeClass,
        ]);
    }
}
