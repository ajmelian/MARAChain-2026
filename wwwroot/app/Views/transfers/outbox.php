<?php

declare(strict_types=1);

/**
 * Outbox view — transfers sent by the authenticated user.
 *
 * @var array<int, \App\Entities\DocumentTransfer> $transfers List of sent transfers
 * @var string|null $statusFilter Current status filter (null = all)
 * @var string $title Page title
 * @var string $current Active navigation item
 */

use App\Entities\DocumentTransfer;

$this->extend('layouts/main');
$this->section('styles');
?>
<link rel="stylesheet" href="/assets/css/inbox.css">
<?php
$this->endSection();

$this->section('content');

// ── Status labels (Spanish) ──
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

// ── Status badge color mapping ──
$statusBadges = [
    'PENDING_RECIPIENT' => 'badge-warning',
    'READY'             => 'badge-info',
    'SENDING'           => 'badge-info',
    'SENT'              => 'badge-info',
    'AVAILABLE'         => 'badge-success',
    'ACCESSED'          => 'badge-primary',
    'DOWNLOADED'        => 'badge-primary',
    'ACCEPTED'          => 'badge-success',
    'REJECTED'          => 'badge-danger',
    'EXPIRED'           => 'badge-secondary',
    'REVOKED'           => 'badge-secondary',
    'FAILED'            => 'badge-danger',
];

$currentFilter = $statusFilter ?? null;
$filterParams  = [
    ''           => 'Todos',
    'PENDING_RECIPIENT' => 'Pendientes',
    'SENT'       => 'Enviados',
    'ACCESSED'   => 'Accedidos',
    'DOWNLOADED' => 'Descargados',
    'ACCEPTED'   => 'Aceptados',
    'REVOKED'    => 'Revocados',
];
?>

<!-- Block Header -->
<div class="block-header">
    <div class="row clearfix">
        <div class="col-lg-5 col-md-5 col-sm-12">
            <h2>Documentos enviados</h2>
        </div>
        <div class="col-lg-7 col-md-7 col-sm-12">
            <ul class="breadcrumb float-md-right padding-0">
                <li class="breadcrumb-item"><a href="/"><i class="zmdi zmdi-home"></i></a></li>
                <li class="breadcrumb-item"><a href="/transfers/outbox">Enviados</a></li>
                <li class="breadcrumb-item active">Lista</li>
            </ul>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">

        <!-- Search & Filter Card -->
        <div class="card">
            <div class="body">
                <div class="row clearfix">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchOutbox" placeholder="Buscar por destinatario o documento...">
                            <span class="input-group-addon">
                                <i class="zmdi zmdi-search"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="/transfers/new" class="btn btn-primary btn-round">
                            <i class="zmdi zmdi-plus"></i> Nuevo envio
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Filter Buttons -->
        <div class="card">
            <div class="body">
                <div class="row clearfix">
                    <div class="col-md-12">
                        <ul class="nav nav-pills">
                            <?php foreach ($filterParams as $statusKey => $filterLabel): ?>
                                <?php
                                $href        = $statusKey === '' ? '/transfers/outbox' : '/transfers/outbox?status=' . urlencode($statusKey);
                                $isActive    = ($currentFilter === $statusKey) || ($currentFilter === null && $statusKey === '');
                                $activeClass = $isActive ? 'btn-primary' : 'btn-default';
                                ?>
                                <li class="nav-item">
                                    <a href="<?= $href ?>" class="btn <?= $activeClass ?> btn-sm m-r-5">
                                        <?= esc($filterLabel) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfers List -->
        <div class="card">
            <div class="header">
                <h2><strong>Transferencias</strong> enviadas</h2>
            </div>
            <div class="body">
                <?php if (empty($transfers)): ?>
                    <!-- Empty State -->
                    <div class="text-center p-5">
                        <i class="zmdi zmdi-mail-send" style="font-size: 64px; color: #ccc;"></i>
                        <h4 class="m-t-20">No has enviado ninguna transferencia</h4>
                        <p class="text-muted">Los documentos que envies a otros usuarios apareceran aqui.</p>
                        <a href="/transfers/new" class="btn btn-primary btn-round m-t-10">
                            <i class="zmdi zmdi-plus"></i> Enviar un documento
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Transfer rows as media list (Alpino style) -->
                    <ul class="list-unstyled mail_list" id="outboxList">
                        <?php foreach ($transfers as $transfer): ?>
                            <?php
                            /* @var DocumentTransfer $transfer */
                            $status     = $transfer->status;
                            $badgeClass = $statusBadges[$status] ?? 'badge-secondary';
                            $label      = $statusLabels[$status] ?? $status;
                            $createdAt  = $transfer->createdAt instanceof \CodeIgniter\I18n\Time
                                ? $transfer->createdAt->humanize()
                                : date('d M', strtotime((string) $transfer->createdAt));
                            $isUnread   = in_array($status, ['PENDING_RECIPIENT', 'SENDING'], true);
                            $rowClass   = $isUnread ? 'media unread' : 'media';
                            ?>
                            <li class="<?= $rowClass ?>" onclick="window.location='/transfers/<?= esc($transfer->id) ?>'" style="cursor: pointer;">
                                <div class="controls">
                                    <div class="checkbox">
                                        <input type="checkbox" id="chk_<?= esc($transfer->id) ?>" value="<?= esc($transfer->id) ?>">
                                        <label for="chk_<?= esc($transfer->id) ?>"></label>
                                    </div>
                                </div>
                                <div class="media-body">
                                    <div class="thumb">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-info text-white" style="width: 40px; height: 40px; font-size: 16px; font-weight: 600;">
                                            <?= strtoupper(substr(esc($transfer->recipientId), 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div class="media-heading">
                                        <span class="m-r-10">
                                            Para: <?= esc($transfer->recipientId) ?>
                                        </span>
                                        <span class="badge <?= $badgeClass ?>"><?= esc($label) ?></span>
                                        <small class="float-right text-muted"><?= $createdAt ?></small>
                                    </div>
                                    <p class="msg">
                                        <span class="m-r-10">Documento:</span>
                                        <?= esc($transfer->documentId) ?>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
$this->endSection();

$this->section('scripts');
?>
<script>
(function() {
    'use strict';

    // ── Client-side filtering by search term ──
    const searchInput = document.getElementById('searchOutbox');
    const list       = document.getElementById('outboxList');

    if (searchInput && list) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase().trim();
            const rows = list.querySelectorAll('li.media');

            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
})();
</script>
<?php
$this->endSection();
