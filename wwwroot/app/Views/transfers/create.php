<?php

declare(strict_types=1);

/**
 * MARAChain — Nuevo envio de documento.
 *
 * Formulario para crear una nueva transferencia de documento,
 * con seleccion de destinatario (persona fisica/juridica),
 * carga de archivo PDF via Dropzone y opciones de seguridad.
 *
 * @package App\Views\Transfers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.1.1
 *
 * @var array<string, mixed> $countries   Lista de paises ISO 3166-1 alpha-2
 * @var array<string, mixed> $contacts   Lista de contactos del usuario
 */

$this->extend('layouts/main');

?>

<?= $this->section('content') ?>

<section class="content">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h2>Nuevo env&iacute;o de documento</h2>
                </div>
                <div class="col-lg-7 col-md-7 col-sm-12">
                    <ul class="breadcrumb float-md-right padding-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>"><i class="zmdi zmdi-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('/transfers') ?>">Transferencias</a></li>
                        <li class="breadcrumb-item active">Nuevo env&iacute;o</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Nuevo env&iacute;o</strong> de documento</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                    <i class="zmdi zmdi-more"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="<?= base_url('/transfers') ?>">Volver a transferencias</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <?= form_open('transfers/store', [
                            'id'       => 'transfer-form',
                            'class'    => 'needs-validation',
                            'novalidate' => 'novalidate',
                            'enctype'  => 'multipart/form-data',
                        ]) ?>

                        <?= csrf_field() ?>

                        <!-- ============================================
                             RECIPIENT SECTION
                             ============================================ -->
                        <h5 class="m-t-0"><strong>Destinatario</strong></h5>
                        <p class="text-muted">Seleccione el tipo de destinatario y complete los datos.</p>

                        <!-- Recipient type toggle -->
                        <div class="form-group">
                            <label class="m-r-20">Tipo de destinatario</label>
                            <div class="radio inlineblock m-r-20">
                                <input type="radio"
                                       name="recipient_type"
                                       id="recipient-physical"
                                       class="with-gap"
                                       value="physical_person"
                                       checked>
                                <label for="recipient-physical">Persona f&iacute;sica</label>
                            </div>
                            <div class="radio inlineblock">
                                <input type="radio"
                                       name="recipient_type"
                                       id="recipient-legal"
                                       class="with-gap"
                                       value="legal_entity">
                                <label for="recipient-legal">Persona jur&iacute;dica</label>
                            </div>
                        </div>

                        <!-- Physical person fields -->
                        <div id="physical-fields">
                            <div class="row clearfix">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label for="recipient-firstname">Nombre <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="recipient-firstname"
                                               name="recipient_first_name"
                                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                               data-error-pattern="El nombre solo puede contener letras y espacios (1-100 caracteres)."
                                               data-error-required="El nombre es obligatorio."
                                               required>
                                        <div class="invalid-feedback"></div>
                                        <small class="help-info">Letras y espacios, m&aacute;x. 100 caracteres</small>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label for="recipient-lastname">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="recipient-lastname"
                                               name="recipient_last_name"
                                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                               data-error-pattern="Los apellidos solo pueden contener letras y espacios (1-100 caracteres)."
                                               data-error-required="Los apellidos son obligatorios."
                                               required>
                                        <div class="invalid-feedback"></div>
                                        <small class="help-info">Letras y espacios, m&aacute;x. 100 caracteres</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legal entity fields -->
                        <div id="legal-fields" style="display: none;">
                            <div class="row clearfix">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label for="recipient-legalname">Raz&oacute;n social <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="recipient-legalname"
                                               name="recipient_legal_name"
                                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\.\,\-]{1,200}$"
                                               data-error-pattern="La raz&oacute;n social contiene caracteres no v&aacute;lidos (m&aacute;x. 200)."
                                               data-error-required="La raz&oacute;n social es obligatoria.">
                                        <div class="invalid-feedback"></div>
                                        <small class="help-info">Nombre legal de la entidad, m&aacute;x. 200 caracteres</small>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label for="recipient-attentionof">A la atenci&oacute;n de <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="recipient-attentionof"
                                               name="recipient_attention_of"
                                               pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                               data-error-pattern="El campo solo puede contener letras y espacios (1-100 caracteres)."
                                               data-error-required="Indique a qui&eacute;n va dirigido.">
                                        <div class="invalid-feedback"></div>
                                        <small class="help-info">Persona de contacto en la entidad</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Common fields -->
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="recipient-email">Email <span class="text-danger">*</span></label>
                                    <input type="email"
                                           class="form-control"
                                           id="recipient-email"
                                           name="recipient_email"
                                           maxlength="254"
                                           data-error-required="El email es obligatorio."
                                           data-error-email="El email no tiene un formato v&aacute;lido."
                                           required>
                                    <div class="invalid-feedback"></div>
                                    <small class="help-info">Email v&aacute;lido, m&aacute;x. 254 caracteres</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="recipient-phone">Tel&eacute;fono</label>
                                    <input type="tel"
                                           class="form-control"
                                           id="recipient-phone"
                                           name="recipient_phone"
                                           pattern="^\+?[1-9]\d{1,14}$"
                                           data-error-pattern="Formato de tel&eacute;fono no v&aacute;lido (E.164)."
                                           placeholder="+34912345678">
                                    <div class="invalid-feedback"></div>
                                    <small class="help-info">Formato E.164, ej. +34912345678</small>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="recipient-country">Pa&iacute;s <span class="text-danger">*</span></label>
                                    <select class="form-control"
                                            id="recipient-country"
                                            name="recipient_country"
                                            data-error-required="El pa&iacute;s es obligatorio."
                                            required>
                                        <option value="ES" selected>Espa&ntilde;a</option>
                                        <option value="AD">Andorra</option>
                                        <option value="AR">Argentina</option>
                                        <option value="BO">Bolivia</option>
                                        <option value="BR">Brasil</option>
                                        <option value="CL">Chile</option>
                                        <option value="CO">Colombia</option>
                                        <option value="CR">Costa Rica</option>
                                        <option value="CU">Cuba</option>
                                        <option value="DO">Rep&uacute;blica Dominicana</option>
                                        <option value="EC">Ecuador</option>
                                        <option value="SV">El Salvador</option>
                                        <option value="GT">Guatemala</option>
                                        <option value="HN">Honduras</option>
                                        <option value="MX">M&eacute;xico</option>
                                        <option value="NI">Nicaragua</option>
                                        <option value="PA">Panam&aacute;</option>
                                        <option value="PY">Paraguay</option>
                                        <option value="PE">Per&uacute;</option>
                                        <option value="PT">Portugal</option>
                                        <option value="PR">Puerto Rico</option>
                                        <option value="US">Estados Unidos</option>
                                        <option value="UY">Uruguay</option>
                                        <option value="VE">Venezuela</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="recipient-postalcode">C&oacute;digo postal</label>
                                    <input type="text"
                                           class="form-control"
                                           id="recipient-postalcode"
                                           name="recipient_postal_code"
                                           pattern="^\d{5}$"
                                           data-error-pattern="El c&oacute;digo postal debe tener 5 d&iacute;gitos."
                                           placeholder="28001">
                                    <div class="invalid-feedback"></div>
                                    <small class="help-info">5 d&iacute;gitos</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- ============================================
                             DOCUMENT SECTION
                             ============================================ -->
                        <h5><strong>Documento</strong></h5>
                        <p class="text-muted">Informaci&oacute;n del documento a enviar.</p>

                        <div class="row clearfix">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="document-title">T&iacute;tulo <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control"
                                           id="document-title"
                                           name="document_title"
                                           maxlength="255"
                                           data-error-required="El t&iacute;tulo del documento es obligatorio."
                                           required>
                                    <div class="invalid-feedback"></div>
                                    <small class="help-info">M&aacute;x. 255 caracteres</small>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="document-description">Descripci&oacute;n</label>
                                    <textarea class="form-control no-resize"
                                              id="document-description"
                                              name="document_description"
                                              rows="3"
                                              maxlength="2000"
                                              placeholder="Descripci&oacute;n opcional del documento..."></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone file upload -->
                        <div class="form-group">
                            <label>Archivo PDF <span class="text-danger">*</span></label>
                             <div id="dropzone-area"
                                  class="dropzone"
                                 data-error-required="Debe seleccionar un archivo PDF.">
                                <div class="dz-message">
                                    <div class="drag-icon-cph">
                                        <i class="material-icons">touch_app</i>
                                    </div>
                                    <h3 class="m-b-0">Arrastre el archivo aqu&iacute; o haga clic para seleccionar</h3>
                                    <em>(Solo archivos PDF. El documento no se env&iacute;a sin cifrar.)</em>
                                </div>
                                <div class="fallback">
                                    <input name="file" type="file" id="document-file" accept="application/pdf" />
                                </div>
                            </div>
                            <div id="file-error" class="invalid-feedback" style="display: none;"></div>
                            <input type="hidden" id="file-uploaded" name="file_uploaded" value="0">
                            <input type="hidden" name="fileHash" id="file-hash" value="">
                            <div class="alert alert-info m-t-10 m-b-0" role="alert">
                                <i class="zmdi zmdi-lock"></i>
                                <strong>Seguridad:</strong> El documento no se env&iacute;a sin cifrar.
                                Se cifra extremo a extremo antes de la transmisi&oacute;n.
                            </div>
                        </div>

                        <hr>

                        <!-- ============================================
                             OPTIONS SECTION
                             ============================================ -->
                        <h5><strong>Opciones</strong> de env&iacute;o</h5>
                        <p class="text-muted">Configure el nivel de seguridad del env&iacute;o.</p>

                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="security-level">Nivel de seguridad <span class="text-danger">*</span></label>
                                    <select class="form-control"
                                            id="security-level"
                                            name="security_level"
                                            data-error-required="El nivel de seguridad es obligatorio."
                                            required>
                                        <option value="standard">Est&aacute;ndar (cifrado E2E)</option>
                                        <option value="signed">Firmado (cifrado + firma del remitente)</option>
                                        <option value="signed_sealed">Firmado y sellado (m&aacute;xima seguridad)</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <small class="help-info">
                                        <strong>Est&aacute;ndar:</strong> Cifrado extremo a extremo.<br>
                                        <strong>Firmado:</strong> A&ntilde;ade firma del remitente.<br>
                                        <strong>Firmado y sellado:</strong> Firma + sello de tiempo.
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="form-group m-t-30">
                                    <div class="checkbox">
                                        <input id="require-signature"
                                               type="checkbox"
                                               name="requires_signature"
                                               value="1">
                                        <label for="require-signature">
                                            Requerir mi firma como remitente
                                        </label>
                                    </div>
                                    <small class="help-info">
                                        Se generar&aacute; una solicitud de firma adicional para el remitente.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Submit -->
                        <div class="form-group m-b-0">
                            <button type="submit"
                                    id="btn-submit"
                                    class="btn btn-primary btn-round waves-effect">
                                <i class="zmdi zmdi-mail-send"></i>&nbsp;Enviar documento
                            </button>
                            <a href="<?= base_url('/transfers') ?>" class="btn btn-simple btn-round waves-effect">
                                Cancelar
                            </a>
                        </div>

                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('page-scripts') ?>

<script src="/assets/js/dropzone-init.js"></script>

<script>
/**
 * Transfer create form validation and interactivity.
 *
 * Handles:
 *   - Recipient type toggle (physical / legal)
 *   - Bootstrap 5 needs-validation with custom regex patterns
 *   - File upload validation (vanilla dropzone-init.js)
 *   - Form submit interception for full validation
 *
 * @since 1.4.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
(function() {
    'use strict';

    // ── DOM references ──
    var form            = document.getElementById('transfer-form');
    var btnSubmit       = document.getElementById('btn-submit');
    var radioPhysical   = document.getElementById('recipient-physical');
    var radioLegal      = document.getElementById('recipient-legal');
    var physicalFields  = document.getElementById('physical-fields');
    var legalFields     = document.getElementById('legal-fields');
    var fileUploadedInput = document.getElementById('file-uploaded');
    var fileErrorEl       = document.getElementById('file-error');

    // ── Recipient type toggle ──
    function toggleRecipientType(type) {
        if (type === 'legal_entity') {
            physicalFields.style.display = 'none';
            legalFields.style.display    = 'block';

            // Physical fields not required
            setRequired('recipient-firstname', false);
            setRequired('recipient-lastname', false);

            // Legal fields required
            setRequired('recipient-legalname', true);
            setRequired('recipient-attentionof', true);
        } else {
            physicalFields.style.display = 'block';
            legalFields.style.display    = 'none';

            // Physical fields required
            setRequired('recipient-firstname', true);
            setRequired('recipient-lastname', true);

            // Legal fields not required
            setRequired('recipient-legalname', false);
            setRequired('recipient-attentionof', false);
        }
    }

    function setRequired(id, isRequired) {
        var el = document.getElementById(id);
        if (!el) return;
        if (isRequired) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
            // Clear validation
            el.classList.remove('is-invalid');
            el.classList.remove('is-valid');
        }
    }

    radioPhysical.addEventListener('change', function() {
        toggleRecipientType('physical_person');
    });
    radioLegal.addEventListener('change', function() {
        toggleRecipientType('legal_entity');
    });

    // ── Validation helpers ──
    var validators = {
        'recipient_first_name': /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'recipient_last_name':  /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'recipient_legal_name': /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\.\,\-]{1,200}$/,
        'recipient_attention_of': /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'recipient_phone':      /^\+?[1-9]\d{1,14}$/,
        'recipient_postal_code': /^\d{5}$/,
    };

    /**
     * Validate a single field by its name.
     *
     * @param {string} name Field name attribute
     * @return {boolean} True if valid
     */
    function validateField(name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return true;

        // Skip disabled/hidden fields
        if (field.disabled || field.type === 'hidden') return true;

        var value   = field.value ? field.value.trim() : '';
        var pattern = validators[name];
        var feedback = field.closest('.form-group').querySelector('.invalid-feedback');
        if (!feedback) return true;

        // Check required
        if (field.hasAttribute('required') && value === '') {
            var msg = field.getAttribute('data-error-required') || 'Este campo es obligatorio.';
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedback.textContent = msg;
            return false;
        }

        // Check pattern
        if (value !== '' && pattern && !pattern.test(value)) {
            var msg = field.getAttribute('data-error-pattern') || 'Formato no v&aacute;lido.';
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedback.textContent = msg;
            return false;
        }

        // Check email type
        if (field.type === 'email' && value !== '') {
            var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailPattern.test(value)) {
                var msg = field.getAttribute('data-error-email') || 'Email no v&aacute;lido.';
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                feedback.textContent = msg;
                return false;
            }
        }

        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        feedback.textContent = '';
        return true;
    }

    /**
     * Validate all form fields.
     *
     * @return {boolean} True if entire form is valid
     */
    function validateForm() {
        var valid = true;

        // Validate each known field
        Object.keys(validators).forEach(function(name) {
            if (!validateField(name)) {
                valid = false;
            }
        });

        // Email validation
        if (!validateField('recipient_email')) {
            valid = false;
        }

        // Recipient type specific validation
        var type = document.querySelector('input[name="recipient_type"]:checked');
        if (type) {
            if (type.value === 'physical_person') {
                ['recipient_first_name', 'recipient_last_name'].forEach(function(name) {
                    if (!validateField(name)) valid = false;
                });
            } else {
                ['recipient_legal_name', 'recipient_attention_of'].forEach(function(name) {
                    if (!validateField(name)) valid = false;
                });
            }
        }

        // Document title
        var title = form.querySelector('[name="document_title"]');
        if (title && title.hasAttribute('required') && (!title.value || !title.value.trim())) {
            var fb = title.closest('.form-group').querySelector('.invalid-feedback');
            var msg = title.getAttribute('data-error-required') || 'Obligatorio.';
            title.classList.add('is-invalid');
            if (fb) fb.textContent = msg;
            valid = false;
        }

        // File upload check (via dropzone-init.js)
        if (!window._selectedFile) {
            fileErrorEl.textContent = 'Debe seleccionar un archivo PDF.';
            fileErrorEl.style.display = 'block';
            valid = false;
        } else {
            fileErrorEl.style.display = 'none';
        }

        return valid;
    }

    // ── Real-time validation on blur ──
    form.addEventListener('blur', function(e) {
        var field = e.target;
        if (field.name && validators[field.name]) {
            validateField(field.name);
        }
    }, true);

    // ── Submit handler ──
    form.addEventListener('submit', function(e) {
        // Prevent default HTML5 validation
        e.preventDefault();
        e.stopPropagation();

        // Run custom validation
        if (!validateForm()) {
            // Focus first invalid field
            var firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return;
        }

        // Set file hash before submission
        var hashField = document.getElementById('file-hash');
        if (window._fileHash && hashField) {
            hashField.value = window._fileHash;
        }

        // Disable button to prevent double submit
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="zmdi zmdi-spinner zmdi-hc-spin"></i>&nbsp;Enviando...';

        // Process file: convert to blob and add to form
        if (window._selectedFile) {
            var dt = new DataTransfer();
            dt.items.add(window._selectedFile);
            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'document_file';
            fileInput.files = dt.files;
            fileInput.style.display = 'none';
            form.appendChild(fileInput);
        }

        // Submit the form natively
        form.submit();
    });

})();
</script>

<?= $this->endSection() ?><!-- end page-scripts -->
