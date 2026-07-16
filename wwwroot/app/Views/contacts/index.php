<?php

declare(strict_types=1);

/**
 * MARAChain — Listado de contactos (agenda).
 *
 * Muestra los contactos del usuario autenticado en formato tabla,
 * con busqueda en vivo, modal para crear/editar contactos,
 * e indicador de estado de identidad.
 *
 * @package App\Views\Contacts
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.1.1
 *
 * @var \App\Entities\Contact[] $contacts Lista de contactos del usuario
 */

$this->extend('layouts/main');

?>

<?= $this->section('content') ?>

<section class="content contact">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h2>Contactos</h2>
                </div>
                <div class="col-lg-7 col-md-7 col-sm-12">
                    <ul class="breadcrumb float-md-end padding-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>"><i class="zmdi zmdi-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">App</a></li>
                        <li class="breadcrumb-item active">Contactos</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12">
                <!-- Search bar -->
                <div class="card">
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-md-6 col-sm-12">
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           id="contacts-search"
                                           placeholder="Buscar contactos..."
                                           aria-label="Buscar contactos">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button">
                                            <i class="zmdi zmdi-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 text-end">
                                <button type="button"
                                        class="btn btn-primary btn-round waves-effect"
                                        data-bs-toggle="modal"
                                        data-bs-target="#contact-modal"
                                        data-mode="create">
                                    <i class="zmdi zmdi-account-add"></i>&nbsp;Nuevo contacto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contacts table -->
                <div class="card">
                    <div class="body">
                        <?php if (empty($contacts)): ?>
                        <!-- Empty state -->
                        <div class="text-center p-5">
                            <div class="mb-5">
                                <i class="zmdi zmdi-accounts zmdi-hc-5x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No hay contactos</h5>
                            <p class="text-muted">A&ntilde;ada su primer contacto para comenzar a enviar documentos.</p>
                            <button type="button"
                                    class="btn btn-primary btn-round waves-effect"
                                    data-bs-toggle="modal"
                                    data-bs-target="#contact-modal"
                                    data-mode="create">
                                <i class="zmdi zmdi-account-add"></i>&nbsp;Nuevo contacto
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 c_list" id="contacts-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th data-breakpoints="xs">Email</th>
                                        <th data-breakpoints="xs">Tipo</th>
                                        <th data-breakpoints="xs">Estado</th>
                                        <th data-breakpoints="xs sm">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                    <?php
                                    $displayName = '';
                                    if ($contact->contactType === 'physical_person') {
                                        $displayName = esc($contact->firstName ?? '') . ' ' . esc($contact->lastName ?? '');
                                    } else {
                                        $displayName = esc($contact->legalName ?? '');
                                    }
                                    $displayName = trim($displayName) ?: '—';
                                    ?>
                                    <tr class="contact-row"
                                        data-name="<?= esc(strtolower($displayName)) ?>"
                                        data-email="<?= esc(strtolower($contact->emailPrimary)) ?>">
                                        <td>
                                            <div class="media">
                                                <div class="media-body">
                                                    <p class="c_name mb-0">
                                                        <?= $displayName ?>
                                                        <?php if ($contact->isLinked()): ?>
                                                        <span class="badge bg-info text-dark ms-3 hidden-sm-down" title="Usuario registrado">
                                                            <i class="zmdi zmdi-accounts"></i>
                                                        </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="email">
                                                <i class="zmdi zmdi-email me-3"></i>
                                                <?= esc($contact->emailPrimary) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($contact->contactType === 'physical_person'): ?>
                                            <span class="badge bg-secondary">F&iacute;sica</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Jur&iacute;dica</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = match ($contact->identityStatus) {
                                                'pending'  => 'bg-warning text-dark',
                                                'invited'  => 'bg-info text-dark',
                                                'verified' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                default    => 'bg-secondary',
                                            };
                                            $statusLabel = match ($contact->identityStatus) {
                                                'pending'  => 'Pendiente',
                                                'invited'  => 'Invitado',
                                                'verified' => 'Verificado',
                                                'rejected' => 'Rechazado',
                                                default    => $contact->identityStatus,
                                            };
                                            ?>
                                            <span class="badge <?= $statusBadge ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-icon btn-neutral btn-icon-mini btn-edit-contact"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#contact-modal"
                                                    data-mode="edit"
                                                    data-id="<?= esc($contact->id) ?>"
                                                    data-contact-type="<?= esc($contact->contactType) ?>"
                                                    data-first-name="<?= esc($contact->firstName ?? '') ?>"
                                                    data-last-name="<?= esc($contact->lastName ?? '') ?>"
                                                    data-legal-name="<?= esc($contact->legalName ?? '') ?>"
                                                    data-attention-of="<?= esc($contact->attentionOf ?? '') ?>"
                                                    data-email="<?= esc($contact->emailPrimary) ?>"
                                                    data-phone="<?= esc($contact->phone ?? '') ?>"
                                                    data-country="<?= esc($contact->country) ?>"
                                                    data-postal-code="<?= esc($contact->postalCode ?? '') ?>"
                                                    title="Editar contacto">
                                                <i class="zmdi zmdi-edit"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-icon btn-neutral btn-icon-mini btn-delete-contact"
                                                    data-id="<?= esc($contact->id) ?>"
                                                    data-name="<?= esc($displayName) ?>"
                                                    title="Eliminar contacto">
                                                <i class="zmdi zmdi-delete"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     CONTACT MODAL (Create / Edit)
     ============================================ -->
<div class="modal fade"
     id="contact-modal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="contact-modal-label"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <?= form_open('contacts/store', [
                'id'       => 'contact-form',
                'class'    => 'needs-validation',
                'novalidate' => 'novalidate',
            ]) ?>
            <?= csrf_field() ?>
            <input type="hidden" name="contact_id" id="contact-id" value="">

            <div class="modal-header">
                <h4 class="title" id="contact-modal-label">Nuevo contacto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Contact type -->
                <div class="mb-3">
                    <label class="me-5">Tipo de contacto <span class="text-danger">*</span></label>
                    <div class="radio inlineblock me-5">
                        <input type="radio"
                               name="contact_type"
                               id="contact-type-physical"
                               class="with-gap"
                               value="physical_person"
                               checked>
                        <label for="contact-type-physical">Persona f&iacute;sica</label>
                    </div>
                    <div class="radio inlineblock">
                        <input type="radio"
                               name="contact_type"
                               id="contact-type-legal"
                               class="with-gap"
                               value="legal_entity">
                        <label for="contact-type-legal">Persona jur&iacute;dica</label>
                    </div>
                </div>

                <!-- Physical person fields -->
                <div id="modal-physical-fields">
                    <div class="row clearfix">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="contact-firstname">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="contact-firstname"
                                       name="first_name"
                                       pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                       data-error-pattern="El nombre solo puede contener letras y espacios (1-100 caracteres)."
                                       data-error-required="El nombre es obligatorio."
                                       required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="contact-lastname">Apellidos <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="contact-lastname"
                                       name="last_name"
                                       pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                       data-error-pattern="Los apellidos solo pueden contener letras y espacios (1-100 caracteres)."
                                       data-error-required="Los apellidos son obligatorios."
                                       required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legal entity fields -->
                <div id="modal-legal-fields" style="display: none;">
                    <div class="row clearfix">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="contact-legalname">Raz&oacute;n social <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="contact-legalname"
                                       name="legal_name"
                                       pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\.\,\-]{1,200}$"
                                       data-error-pattern="La raz&oacute;n social contiene caracteres no v&aacute;lidos."
                                       data-error-required="La raz&oacute;n social es obligatoria.">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label for="contact-attentionof">A la atenci&oacute;n de <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="contact-attentionof"
                                       name="attention_of"
                                       pattern="^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$"
                                       data-error-pattern="El campo solo puede contener letras y espacios."
                                       data-error-required="Indique a qui&eacute;n va dirigido.">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common fields -->
                <div class="row clearfix">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="mb-3">
                            <label for="contact-email">Email <span class="text-danger">*</span></label>
                            <input type="email"
                                   class="form-control"
                                   id="contact-email"
                                   name="email_primary"
                                   maxlength="254"
                                   data-error-required="El email es obligatorio."
                                   data-error-email="El email no tiene un formato v&aacute;lido."
                                   required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="mb-3">
                            <label for="contact-phone">Tel&eacute;fono</label>
                            <input type="tel"
                                   class="form-control"
                                   id="contact-phone"
                                   name="phone"
                                   pattern="^\+?[1-9]\d{1,14}$"
                                   data-error-pattern="Formato de tel&eacute;fono no v&aacute;lido (E.164)."
                                   placeholder="+34912345678">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="row clearfix">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="mb-3">
                            <label for="contact-country">Pa&iacute;s <span class="text-danger">*</span></label>
                            <select class="form-control"
                                    id="contact-country"
                                    name="country"
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
                                <option value="US">Estados Unidos</option>
                                <option value="UY">Uruguay</option>
                                <option value="VE">Venezuela</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="mb-3">
                            <label for="contact-postalcode">C&oacute;digo postal</label>
                            <input type="text"
                                   class="form-control"
                                   id="contact-postalcode"
                                   name="postal_code"
                                   pattern="^\d{5}$"
                                   data-error-pattern="El c&oacute;digo postal debe tener 5 d&iacute;gitos."
                                   placeholder="28001">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-simple btn-round waves-effect" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary btn-round waves-effect">
                    <i class="zmdi zmdi-account-add"></i>&nbsp;Guardar contacto
                </button>
            </div>

            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade"
     id="delete-modal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="delete-modal-label"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="title" id="delete-modal-label">Eliminar contacto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="delete-modal-text">&iquest;Est&aacute; seguro de que desea eliminar este contacto?</p>
                <p class="text-muted">Esta acci&oacute;n no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-simple btn-round waves-effect" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <?= form_open('contacts/delete', ['id' => 'delete-form', 'method' => 'POST']) ?>
                <?= csrf_field() ?>
                <input type="hidden" name="contact_id" id="delete-contact-id" value="">
                <button type="submit" class="btn btn-danger btn-round waves-effect">
                    <i class="zmdi zmdi-delete"></i>&nbsp;Eliminar
                </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('page-scripts') ?>

<script>
/**
 * Contacts page — search, CRUD modals, and form validation.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
(function() {
    'use strict';

    // ── DOM refs ──
    var searchInput       = document.getElementById('contacts-search');
    var contactsTable     = document.getElementById('contacts-table');
    var contactModal      = document.getElementById('contact-modal');
    var contactForm       = document.getElementById('contact-form');
    var deleteModal       = document.getElementById('delete-modal');

    // ── Search filter ──
    if (searchInput && contactsTable) {
        searchInput.addEventListener('keyup', function() {
            var query = this.value.toLowerCase().trim();
            var rows  = contactsTable.querySelectorAll('.contact-row');

            rows.forEach(function(row) {
                var name  = row.getAttribute('data-name') || '';
                var email = row.getAttribute('data-email') || '';
                var match = name.indexOf(query) !== -1 || email.indexOf(query) !== -1;
                row.style.display = match ? '' : 'none';
            });
        });
    }

    // ── Modal contact type toggle ──
    var modalRadioPhysical = document.getElementById('contact-type-physical');
    var modalRadioLegal    = document.getElementById('contact-type-legal');
    var modalPhysicalFields = document.getElementById('modal-physical-fields');
    var modalLegalFields    = document.getElementById('modal-legal-fields');

    function toggleModalRecipientType(type) {
        if (type === 'legal_entity') {
            modalPhysicalFields.style.display = 'none';
            modalLegalFields.style.display    = 'block';
            setModalRequired('contact-firstname', false);
            setModalRequired('contact-lastname', false);
            setModalRequired('contact-legalname', true);
            setModalRequired('contact-attentionof', true);
        } else {
            modalPhysicalFields.style.display = 'block';
            modalLegalFields.style.display    = 'none';
            setModalRequired('contact-firstname', true);
            setModalRequired('contact-lastname', true);
            setModalRequired('contact-legalname', false);
            setModalRequired('contact-attentionof', false);
        }
    }

    function setModalRequired(id, required) {
        var el = document.getElementById(id);
        if (!el) return;
        if (required) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
            el.classList.remove('is-invalid', 'is-valid');
        }
    }

    if (modalRadioPhysical) {
        modalRadioPhysical.addEventListener('change', function() {
            toggleModalRecipientType('physical_person');
        });
    }
    if (modalRadioLegal) {
        modalRadioLegal.addEventListener('change', function() {
            toggleModalRecipientType('legal_entity');
        });
    }

    // ── Modal show: set mode (create / edit) ──
    $('#contact-modal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var mode   = button.data('mode');
        var modal  = $(this);

        // Reset form
        contactForm.reset();
        contactForm.querySelectorAll('.is-invalid, .is-valid').forEach(function(el) {
            el.classList.remove('is-invalid', 'is-valid');
        });

        if (mode === 'edit') {
            modal.find('#contact-modal-label').text('Editar contacto');
            modal.find('#contact-id').val(button.data('id'));
            var type = button.data('contact-type');
            if (type === 'legal_entity') {
                modal.find('#contact-type-legal').prop('checked', true);
                toggleModalRecipientType('legal_entity');
            } else {
                modal.find('#contact-type-physical').prop('checked', true);
                toggleModalRecipientType('physical_person');
            }
            modal.find('#contact-firstname').val(button.data('first-name'));
            modal.find('#contact-lastname').val(button.data('last-name'));
            modal.find('#contact-legalname').val(button.data('legal-name'));
            modal.find('#contact-attentionof').val(button.data('attention-of'));
            modal.find('#contact-email').val(button.data('email'));
            modal.find('#contact-phone').val(button.data('phone'));
            modal.find('#contact-country').val(button.data('country'));
            modal.find('#contact-postalcode').val(button.data('postal-code'));
        } else {
            modal.find('#contact-modal-label').text('Nuevo contacto');
            modal.find('#contact-id').val('');
            modal.find('#contact-type-physical').prop('checked', true);
            toggleModalRecipientType('physical_person');
        }
    });

    // ── Delete modal ──
    $(document).on('click', '.btn-delete-contact', function() {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#delete-contact-id').val(id);
        $('#delete-modal-text').text('¿Está seguro de que desea eliminar a «' + name + '»?');
        $('#delete-modal').modal('show');
    });

    // ── Validation helpers ──
    var modalValidators = {
        'first_name':   /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'last_name':    /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'legal_name':   /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\.\,\-]{1,200}$/,
        'attention_of': /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        'phone':        /^\+?[1-9]\d{1,14}$/,
        'postal_code':  /^\d{5}$/,
    };

    function validateModalField(name) {
        var field = contactForm.querySelector('[name="' + name + '"]');
        if (!field) return true;
        if (field.disabled || field.type === 'hidden') return true;

        var value    = field.value ? field.value.trim() : '';
        var pattern  = modalValidators[name];
        var feedback = field.closest('.mb-3').querySelector('.invalid-feedback');
        if (!feedback) return true;

        if (field.hasAttribute('required') && value === '') {
            var msg = field.getAttribute('data-error-required') || 'Este campo es obligatorio.';
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedback.textContent = msg;
            return false;
        }

        if (value !== '' && pattern && !pattern.test(value)) {
            var msg = field.getAttribute('data-error-pattern') || 'Formato no válido.';
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedback.textContent = msg;
            return false;
        }

        if (field.type === 'email' && value !== '') {
            var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailPattern.test(value)) {
                var msg = field.getAttribute('data-error-email') || 'Email no válido.';
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

    function validateModalForm() {
        var valid = true;
        Object.keys(modalValidators).forEach(function(name) {
            if (!validateModalField(name)) valid = false;
        });

        // Email
        if (!validateModalField('email_primary')) valid = false;

        // Type-specific fields
        var type = document.querySelector('input[name="contact_type"]:checked');
        if (type) {
            if (type.value === 'physical_person') {
                ['first_name', 'last_name'].forEach(function(n) {
                    if (!validateModalField(n)) valid = false;
                });
            } else {
                ['legal_name', 'attention_of'].forEach(function(n) {
                    if (!validateModalField(n)) valid = false;
                });
            }
        }

        return valid;
    }

    // Real-time validation on blur
    contactForm.addEventListener('blur', function(e) {
        var field = e.target;
        if (field.name && modalValidators[field.name]) {
            validateModalField(field.name);
        }
    }, true);

    // Submit validation
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!validateModalForm()) {
            var firstInvalid = contactForm.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.focus();
            return;
        }

        contactForm.submit();
    });

})();
</script>

<?= $this->endSection() ?>
