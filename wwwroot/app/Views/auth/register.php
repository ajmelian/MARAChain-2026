<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="block-header">
    <div class="row clearfix">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <h2>Crear cuenta</h2>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        <div class="card">
            <div class="body">
                <div class="text-center mb-5">
                    <img src="/assets/images/logo.svg" width="64" height="64" alt="MARAChain">
                    <h4 class="mt-5">MARAChain</h4>
                    <p class="text-muted">Crea tu cuenta para empezar</p>
                </div>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?= esc(session('error')) ?>
                    </div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
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

                <form action="<?= url_to('register') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Identity Type -->
                    <div class="mb-3">
                        <label for="register-identity-type">Tipo de identidad</label>
                        <select class="form-control" id="register-identity-type" name="identity_type" required>
                            <option value="physical" <?= old('identity_type') === 'physical' ? 'selected' : '' ?>>
                                Persona f&iacute;sica
                            </option>
                            <option value="legal" <?= old('identity_type') === 'legal' ? 'selected' : '' ?>>
                                Persona jur&iacute;dica
                            </option>
                        </select>
                    </div>

                    <!-- First Name / Legal Name (dynamic label via JS) -->
                    <div class="mb-3">
                        <label for="register-first-name" id="label-first-name">Nombre</label>
                        <input type="text"
                               class="form-control"
                               id="register-first-name"
                               name="first_name"
                               placeholder="Nombre"
                               value="<?= old('first_name') ?>"
                               required
                               maxlength="100"
                               pattern="^[a-zA-ZÀ-ÿ\u00f1\u00d1\s'-]{2,100}$">
                        <div class="invalid-feedback">
                            Introduce un nombre v&aacute;lido (2-100 caracteres).
                        </div>
                    </div>

                    <!-- Last Name (for physical persons) -->
                    <div class="mb-3" id="last-name-group">
                        <label for="register-last-name">Apellidos</label>
                        <input type="text"
                               class="form-control"
                               id="register-last-name"
                               name="last_name"
                               placeholder="Apellidos"
                               value="<?= old('last_name') ?>"
                               maxlength="150">
                    </div>

                    <!-- Legal Name (for legal entities) -->
                    <div class="mb-3" id="legal-name-group" style="display:none;">
                        <label for="register-legal-name">Raz&oacute;n social</label>
                        <input type="text"
                               class="form-control"
                               id="register-legal-name"
                               name="legal_name"
                               placeholder="Raz&oacute;n social"
                               value="<?= old('legal_name') ?>"
                               maxlength="200">
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="register-email">Correo electr&oacute;nico</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="zmdi zmdi-email"></i></span>
                            </div>
                            <input type="email"
                                   class="form-control"
                                   id="register-email"
                                   name="email"
                                   inputmode="email"
                                   autocomplete="email"
                                   placeholder="correo@ejemplo.com"
                                   value="<?= old('email') ?>"
                                   required
                                   pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$">
                            <div class="invalid-feedback">
                                Introduce un correo electr&oacute;nico v&aacute;lido.
                            </div>
                        </div>
                    </div>

                    <!-- Username (auto-generated from email) -->
                    <div class="mb-3">
                        <label for="register-username">Nombre de usuario</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="zmdi zmdi-account"></i></span>
                            </div>
                            <input type="text"
                                   class="form-control"
                                   id="register-username"
                                   name="username"
                                   autocomplete="username"
                                   placeholder="nombreusuario"
                                   value="<?= old('username') ?>"
                                   required
                                   minlength="3"
                                   maxlength="30"
                                   pattern="^[a-zA-Z0-9_]{3,30}$">
                            <div class="invalid-feedback">
                                El nombre de usuario debe tener entre 3 y 30 caracteres alfanum&eacute;ricos o guiones bajos.
                            </div>
                        </div>
                        <small class="text-muted">Se genera autom&aacute;ticamente a partir del correo electr&oacute;nico.</small>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="register-password">Contrase&ntilde;a</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="zmdi zmdi-lock"></i></span>
                            </div>
                            <input type="password"
                                   class="form-control"
                                   id="register-password"
                                   name="password"
                                   autocomplete="new-password"
                                   placeholder="Contrase&ntilde;a"
                                   required
                                   minlength="8">
                            <div class="invalid-feedback">
                                La contrase&ntilde;a debe tener al menos 8 caracteres.
                            </div>
                        </div>
                    </div>

                    <!-- Password Confirm -->
                    <div class="mb-4">
                        <label for="register-password-confirm">Confirmar contrase&ntilde;a</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="zmdi zmdi-lock-outline"></i></span>
                            </div>
                            <input type="password"
                                   class="form-control"
                                   id="register-password-confirm"
                                   name="password_confirm"
                                   autocomplete="new-password"
                                   placeholder="Repite la contrase&ntilde;a"
                                   required
                                   minlength="8">
                            <div class="invalid-feedback">
                                Las contrase&ntilde;as deben coincidir.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-round waves-effect">
                        <i class="zmdi zmdi-account-add"></i> Crear cuenta
                    </button>
                </form>

                <div class="text-center mt-5">
                    <p class="text-muted">
                        &iquest;Ya tienes cuenta?
                        <a href="<?= url_to('login') ?>">Iniciar sesi&oacute;n</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // ── Identity type toggle ──
    function toggleIdentityType() {
        var type = $('#register-identity-type').val();
        if (type === 'legal') {
            $('#last-name-group').hide();
            $('#legal-name-group').show();
            $('#label-first-name').text('Nombre / Raz&oacute;n social');
            $('#register-legal-name').prop('required', true);
            $('#register-last-name').prop('required', false);
        } else {
            $('#last-name-group').show();
            $('#legal-name-group').hide();
            $('#label-first-name').text('Nombre');
            $('#register-legal-name').prop('required', false);
            $('#register-last-name').prop('required', false);
        }
    }

    $('#register-identity-type').on('change', toggleIdentityType);
    toggleIdentityType();

    // ── Auto-generate username from email ──
    $('#register-email').on('blur keyup', function() {
        var email = $(this).val();
        if (email && !$('#register-username').val()) {
            var username = email.split('@')[0]
                .replace(/[^a-zA-Z0-9_]/g, '_')
                .substring(0, 30)
                .replace(/^_+|_+$/g, '');
            if (username.length < 3) {
                username = 'user_' + username;
            }
            $('#register-username').val(username);
        }
    });

    // ── Client-side password match validation ──
    function validatePasswordMatch() {
        var pwd = $('#register-password').val();
        var confirm = $('#register-password-confirm').val();
        if (confirm && pwd !== confirm) {
            $('#register-password-confirm')[0].setCustomValidity('Las contrase&ntilde;as no coinciden.');
        } else {
            $('#register-password-confirm')[0].setCustomValidity('');
        }
    }

    $('#register-password, #register-password-confirm').on('keyup', validatePasswordMatch);

    // ── Bootstrap validation on submit ──
    $('form').on('submit', function(e) {
        validatePasswordMatch();
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>
<?= $this->endSection() ?>
