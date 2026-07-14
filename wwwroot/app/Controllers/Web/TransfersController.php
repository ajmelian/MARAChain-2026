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

        // ── Mock data para MVP ──
        $mockData = [
            [
                'id'          => '880e8400-e29b-41d4-a716-446655440003',
                'senderId'    => '550e8400-e29b-41d4-a716-446655440000',
                'recipientId' => '00000000-0000-4000-a000-000000000001',
                'documentId'  => '770e8400-e29b-41d4-a716-446655440002',
                'status'      => 'AVAILABLE',
                'createdAt'   => '2026-07-13 09:00:00',
            ],
            [
                'id'          => '881e8400-e29b-41d4-a716-446655440004',
                'senderId'    => '00000000-0000-4000-a000-000000000001',
                'recipientId' => '660e8400-e29b-41d4-a716-446655440005',
                'documentId'  => '771e8400-e29b-41d4-a716-446655440006',
                'status'      => 'SENT',
                'createdAt'   => '2026-07-12 15:30:00',
            ],
        ];

        // Convertir arrays a entidades DocumentTransfer
        $transfers = array_map(
            static fn (array $data): DocumentTransfer => new DocumentTransfer($data),
            $mockData
        );

        // Filtrar por estado si se especifico
        if ($statusFilter !== null && $statusFilter !== '') {
            $transfers = array_values(
                array_filter(
                    $transfers,
                    static fn (DocumentTransfer $t): bool => $t->status === $statusFilter
                )
            );
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

        // ── Mock data para MVP ──
        $mockData = [
            [
                'id'          => '880e8400-e29b-41d4-a716-446655440003',
                'senderId'    => '550e8400-e29b-41d4-a716-446655440000',
                'recipientId' => '00000000-0000-4000-a000-000000000001',
                'documentId'  => '770e8400-e29b-41d4-a716-446655440002',
                'status'      => 'AVAILABLE',
                'createdAt'   => '2026-07-13 09:00:00',
            ],
            [
                'id'          => '881e8400-e29b-41d4-a716-446655440004',
                'senderId'    => '00000000-0000-4000-a000-000000000001',
                'recipientId' => '660e8400-e29b-41d4-a716-446655440005',
                'documentId'  => '771e8400-e29b-41d4-a716-446655440006',
                'status'      => 'SENT',
                'createdAt'   => '2026-07-12 15:30:00',
            ],
        ];

        // Convertir arrays a entidades DocumentTransfer
        $transfers = array_map(
            static fn (array $data): DocumentTransfer => new DocumentTransfer($data),
            $mockData
        );

        // Filtrar por estado si se especifico
        if ($statusFilter !== null && $statusFilter !== '') {
            $transfers = array_values(
                array_filter(
                    $transfers,
                    static fn (DocumentTransfer $t): bool => $t->status === $statusFilter
                )
            );
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
            // Transferencia no encontrada — mostrar pagina de error
            $viewContent = '<?php $this->extend("layouts/main"); ?>'
                . '<?php $this->section("content"); ?>'
                . '<div class="block-header">'
                . '    <h2>Transferencia no encontrada</h2>'
                . '</div>'
                . '<div class="card">'
                . '    <div class="body text-center">'
                . '        <i class="zmdi zmdi-alert-circle" style="font-size: 64px; color: #f44336;"></i>'
                . '        <h4 class="m-t-20">La transferencia solicitada no existe</h4>'
                . '        <p class="text-muted">El identificador proporcionado no corresponde a ninguna transferencia.</p>'
                . '        <a href="/transfers/inbox" class="btn btn-primary btn-round m-t-10">'
                . '            <i class="zmdi zmdi-arrow-back"></i> Volver a recibidos'
                . '        </a>'
                . '    </div>'
                . '</div>'
                . '<?php $this->endSection(); ?>';

            return view('string:' . $viewContent, [
                'title'   => 'Transferencia no encontrada',
                'current' => 'inbox',
            ]);
        }

        $transfer = new DocumentTransfer($data);

        // Mapa de etiquetas de estado en espanol
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

        $viewContent = '<?php $this->extend("layouts/main"); ?>'
            . '<?php $this->section("content"); ?>'
            . '<div class="block-header">'
            . '    <div class="row clearfix">'
            . '        <div class="col-lg-5 col-md-5 col-sm-12">'
            . '            <h2>Detalle de transferencia</h2>'
            . '        </div>'
            . '        <div class="col-lg-7 col-md-7 col-sm-12">'
            . '            <ul class="breadcrumb float-md-right padding-0">'
            . '                <li class="breadcrumb-item"><a href="/"><i class="zmdi zmdi-home"></i></a></li>'
            . '                <li class="breadcrumb-item"><a href="/transfers/inbox">Recibidos</a></li>'
            . '                <li class="breadcrumb-item active">Detalle</li>'
            . '            </ul>'
            . '        </div>'
            . '    </div>'
            . '</div>'
            . '<div class="row clearfix">'
            . '    <div class="col-lg-12">'
            . '        <div class="card">'
            . '            <div class="header">'
            . '                <h2><strong>Informacion</strong> de la transferencia</h2>'
            . '            </div>'
            . '            <div class="body">'
            . '                <div class="row">'
            . '                    <div class="col-md-6">'
            . '                        <dl class="row">'
            . '                            <dt class="col-sm-4">ID:</dt>'
            . '                            <dd class="col-sm-8"><code>' . esc($transfer->id) . '</code></dd>'
            . '                            <dt class="col-sm-4">Estado:</dt>'
            . '                            <dd class="col-sm-8"><span class="badge badge-info">' . esc($statusLabel) . '</span></dd>'
            . '                            <dt class="col-sm-4">Remitente:</dt>'
            . '                            <dd class="col-sm-8"><code>' . esc($transfer->senderId) . '</code></dd>'
            . '                            <dt class="col-sm-4">Destinatario:</dt>'
            . '                            <dd class="col-sm-8"><code>' . esc($transfer->recipientId) . '</code></dd>'
            . '                            <dt class="col-sm-4">Documento:</dt>'
            . '                            <dd class="col-sm-8"><code>' . esc($transfer->documentId) . '</code></dd>'
            . '                            <dt class="col-sm-4">Creado:</dt>'
            . '                            <dd class="col-sm-8">' . esc($transfer->createdAt) . '</dd>'
            . '                        </dl>'
            . '                    </div>'
            . '                </div>'
            . '                <div class="m-t-20">'
            . '                    <a href="/transfers/inbox" class="btn btn-simple btn-round waves-effect">'
            . '                        <i class="zmdi zmdi-arrow-back"></i> Volver a recibidos'
            . '                    </a>'
            . '                </div>'
            . '            </div>'
            . '        </div>'
            . '    </div>'
            . '</div>'
            . '<?php $this->endSection(); ?>';

        return view('string:' . $viewContent, [
            'title'   => 'Detalle de transferencia',
            'current' => 'inbox',
        ]);
    }
}
