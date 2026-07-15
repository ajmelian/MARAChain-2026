<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="block-header">
    <div class="row clearfix">
        <div class="col-lg-5 col-md-5 col-sm-12">
            <h2>Detalle de transferencia</h2>
        </div>
        <div class="col-lg-7 col-md-7 col-sm-12">
            <ul class="breadcrumb float-md-right padding-0">
                <li class="breadcrumb-item"><a href="/inbox"><i class="zmdi zmdi-home"></i></a></li>
                <li class="breadcrumb-item"><a href="/inbox">Recibidos</a></li>
                <li class="breadcrumb-item active">Transferencia</li>
            </ul>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-8 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Transferencia <?= esc($transfer->id ?? $transfer['id']) ?></h2>
            </div>
            <div class="body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td><code><?= esc($transfer->id ?? $transfer['id']) ?></code></td>
                            </tr>
                            <tr>
                                <th>Estado</th>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= esc($statusLabel) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Remitente</th>
                                <td><?= esc($transfer->senderId ?? $transfer['senderId']) ?></td>
                            </tr>
                            <tr>
                                <th>Destinatario</th>
                                <td><?= esc($transfer->recipientId ?? $transfer['recipientId']) ?></td>
                            </tr>
                            <tr>
                                <th>Documento</th>
                                <td><?= esc($transfer->documentId ?? $transfer['documentId']) ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de creacion</th>
                                <td><?= esc(is_string($transfer->createdAt ?? null) ? $transfer->createdAt : ($transfer['createdAt'] ?? '')) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="m-t-15">
                    <a href="/inbox" class="btn btn-default">
                        <i class="zmdi zmdi-arrow-back"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
