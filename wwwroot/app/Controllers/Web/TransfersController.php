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
        $transfer = $this->transferModel->freshEntity($id);

        if ($transfer === null) {
            return $this->render('transfers/not_found', [
                'title'   => 'Transferencia no encontrada',
                'current' => 'inbox',
            ]);
        }

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
            'transfer'    => $transfer,
            'statusLabel' => $statusLabel,
            'badgeClass'  => $badgeClass,
        ]);
    }

    /**
     * POST /transfers/{id}/accept — Accept a transfer (web).
     *
     * @param string $id Transfer UUID
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @since 1.5.0
     */
    public function accept(string $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $transfer = $this->transferModel->freshEntity($id);

        if ($transfer === null) {
            return redirect()->to('/inbox')->with('error', 'Transferencia no encontrada.');
        }

        try {
            $this->transferModel->transitionStatus($transfer, 'ACCEPTED');
        } catch (\Throwable $e) {
            return redirect()->to('/inbox')->with('error', $e->getMessage());
        }

        return redirect()->to('/inbox')->with('message', 'Transferencia aceptada.');
    }

    /**
     * POST /transfers/{id}/reject — Reject a transfer (web).
     *
     * @param string $id Transfer UUID
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @since 1.5.0
     */
    public function reject(string $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $transfer = $this->transferModel->freshEntity($id);

        if ($transfer === null) {
            return redirect()->to('/inbox')->with('error', 'Transferencia no encontrada.');
        }

        try {
            $this->transferModel->transitionStatus($transfer, 'REJECTED');
        } catch (\Throwable $e) {
            return redirect()->to('/inbox')->with('error', $e->getMessage());
        }

        return redirect()->to('/inbox')->with('message', 'Transferencia rechazada.');
    }
}
