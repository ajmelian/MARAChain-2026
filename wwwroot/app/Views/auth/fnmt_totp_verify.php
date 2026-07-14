<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="zmdi zmdi-shield-check"></i> <?= esc($title) ?></h5>
            </div>
            <div class="card-body text-center">
                <p class="text-muted">Su certificado FNMT ha sido verificado. Introduzca el codigo TOTP de su aplicacion.</p>

                <form method="post" action="/auth/fnmt/totp-verify" class="needs-validation mt-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="totp_code">Codigo TOTP</label>
                        <input type="text" class="form-control form-control-lg text-center"
                               id="totp_code" name="totp_code"
                               pattern="[0-9]{6}" maxlength="6" minlength="6"
                               placeholder="000000" required autofocus autocomplete="off"
                               style="font-size: 2rem; letter-spacing: 0.5rem;">
                        <div class="invalid-feedback">Introduzca el codigo de 6 digitos.</div>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="zmdi zmdi-sign-in"></i> Acceder
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
