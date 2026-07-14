<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5><i class="zmdi zmdi-alert-triangle"></i> Error de autenticacion FNMT</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <?= esc($error ?? 'No se pudo verificar el certificado FNMT.') ?>
                </div>

                <hr>
                <p class="text-muted small">Causas posibles:</p>
                <ul class="text-muted small">
                    <li>Certificado FNMT no presente o caducado.</li>
                    <li>El navegador no ha enviado el certificado cliente.</li>
                    <li>El certificado no esta emitido por una CA reconocida.</li>
                    <li>El NIF del certificado no es valido.</li>
                </ul>

                <a href="/login" class="btn btn-outline-secondary btn-block mt-3">
                    <i class="zmdi zmdi-arrow-left"></i> Volver al inicio de sesion
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
