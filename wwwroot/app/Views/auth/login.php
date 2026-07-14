<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="block-header">
    <div class="row clearfix">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <h2>Iniciar sesi&oacute;n</h2>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4">
        <div class="card">
            <div class="body">
                <div class="text-center m-b-20">
                    <img src="/assets/images/logo.svg" width="64" height="64" alt="MARAChain">
                    <h4 class="m-t-20">MARAChain</h4>
                    <p class="text-muted">Accede a tu cuenta</p>
                </div>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?= esc(session('error')) ?>
                    </div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <?= esc($error) ?><br>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= esc(session('errors')) ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <?php if (session('message') !== null) : ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?= esc(session('message')) ?>
                    </div>
                <?php endif ?>

                <form action="<?= url_to('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-email"></i></span>
                        </div>
                        <input type="email"
                               class="form-control"
                               id="login-email"
                               name="email"
                               inputmode="email"
                               autocomplete="email"
                               placeholder="Correo electr&oacute;nico"
                               value="<?= old('email') ?>"
                               required
                               pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
                        <div class="invalid-feedback">
                            Introduce un correo electr&oacute;nico v&aacute;lido.
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-lock"></i></span>
                        </div>
                        <input type="password"
                               class="form-control"
                               id="login-password"
                               name="password"
                               inputmode="text"
                               autocomplete="current-password"
                               placeholder="Contrase&ntilde;a"
                               required
                               minlength="8">
                        <div class="invalid-feedback">
                            La contrase&ntilde;a debe tener al menos 8 caracteres.
                        </div>
                    </div>

                    <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                        <div class="form-check mb-3">
                            <label class="form-check-label">
                                <input type="checkbox" name="remember" class="form-check-input"
                                       <?= old('remember') ? 'checked' : '' ?>>
                                Recordarme
                            </label>
                        </div>
                    <?php endif ?>

                    <button type="submit" class="btn btn-primary btn-block btn-round waves-effect">
                        <i class="zmdi zmdi-sign-in"></i> Iniciar sesi&oacute;n
                    </button>
                </form>

                <div class="text-center m-t-20">
                    <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                        <p>
                            <a href="<?= url_to('magic-link') ?>">
                                <i class="zmdi zmdi-email"></i> Enviar enlace m&aacute;gico
                            </a>
                        </p>
                    <?php endif ?>

                    <?php if (setting('Auth.allowRegistration')) : ?>
                        <p class="text-muted">
                            &iquest;No tienes cuenta?
                            <a href="<?= url_to('register') ?>">Crear cuenta</a>
                        </p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Client-side validation for login form
    $('#login-email, #login-password').on('blur', function() {
        validateField(this);
    });

    function validateField(el) {
        if (el.checkValidity()) {
            $(el).removeClass('is-invalid').addClass('is-valid');
            $(el).siblings('.invalid-feedback').hide();
        } else {
            $(el).removeClass('is-valid').addClass('is-invalid');
            $(el).siblings('.invalid-feedback').show();
        }
    }
});
</script>
<?= $this->endSection() ?>
