<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="zmdi zmdi-shield-security"></i> <?= esc($title) ?></h5>
            </div>
            <div class="card-body text-center">
                <p class="text-muted">Escanee el codigo QR con su aplicacion de autenticacion (Google Authenticator, Authy, etc.).</p>

                <div class="my-4">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qrCodeUrl) ?>"
                         alt="QR Code" width="200" height="200">
                </div>

                <p class="small text-muted">
                    <strong>Clave secreta:</strong><br>
                    <code><?= esc($secret) ?></code><br>
                    <small>Introduzcala manualmente si no puede escanear el QR.</small>
                </p>

                <hr>

                <form method="post" action="/auth/fnmt/totp-setup" class="needs-validation mt-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="totp_code">Codigo de verificacion</label>
                        <input type="text" class="form-control" id="totp_code" name="totp_code"
                               pattern="[0-9]{6}" maxlength="6" minlength="6"
                               placeholder="000000" required autocomplete="off">
                        <div class="invalid-feedback">Introduzca el codigo de 6 digitos de su aplicacion.</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="zmdi zmdi-check"></i> Verificar y activar
                    </button>
                </form>
            </div>
            <div class="card-footer text-muted small">
                <i class="zmdi zmdi-info-outline"></i>
                Este segundo factor protege su cuenta. Sin el no podra acceder aunque tenga el certificado FNMT.
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
