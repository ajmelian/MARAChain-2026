<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="block-header">
    <h2>Transferencia no encontrada</h2>
</div>
<div class="card">
    <div class="body text-center">
        <i class="zmdi zmdi-alert-circle" style="font-size: 64px; color: #f44336;"></i>
        <h4 class="mt-5">La transferencia solicitada no existe</h4>
        <p class="text-muted">El identificador proporcionado no corresponde a ninguna transferencia.</p>
        <a href="/inbox" class="btn btn-primary btn-round mt-3">
            <i class="zmdi zmdi-arrow-back"></i> Volver a recibidos
        </a>
    </div>
</div>
<?= $this->endSection() ?>
