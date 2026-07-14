<?php

declare(strict_types=1);

/**
 * MARAChain — Perfil de usuario.
 *
 * Muestra la informacion del perfil del usuario autenticado,
 * incluyendo datos personales, nivel de garantia eIDAS,
 * estado TOTP, dispositivos activos y seccion de seguridad.
 *
 * @package App\Views\Profile
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.1.1
 *
 * @var \App\Entities\User          $user       Usuario autenticado
 * @var \App\Entities\Device[]|null $devices    Dispositivos activos del usuario
 */

$this->extend('layouts/main');

?>

<?= $this->section('content') ?>

<section class="content profile-page">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h2>Mi Perfil</h2>
                </div>
                <div class="col-lg-7 col-md-7 col-sm-12">
                    <ul class="breadcrumb float-md-right padding-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('/') ?>"><i class="zmdi zmdi-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">P&aacute;ginas</a></li>
                        <li class="breadcrumb-item active">Mi Perfil</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <!-- ============================================
                     PROFILE HEADER CARD
                     ============================================ -->
                <div class="card">
                    <div class="body bg-dark profile-header">
                        <div class="row">
                            <div class="col-lg-10 col-md-12">
                                <div class="detail">
                                    <div class="u_name">
                                        <h4>
                                            <?php if ($user->identityType === 'physical'): ?>
                                                <strong><?= esc($user->firstName ?? '') ?></strong>
                                                <?= esc($user->lastName ?? '') ?>
                                            <?php else: ?>
                                                <strong><?= esc($user->legalName ?? '') ?></strong>
                                            <?php endif; ?>
                                        </h4>
                                        <span>
                                            <?= $user->identityType === 'physical' ? 'Persona f&iacute;sica' : 'Persona jur&iacute;dica' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-12 user_earnings">
                                <h6>Nivel de garant&iacute;a</h6>
                                <?php
                                $guaranteeBadge = match ($user->guaranteeLevel) {
                                    'low'         => 'badge-secondary',
                                    'substantial' => 'badge-info',
                                    'high'        => 'badge-success',
                                    default       => 'badge-secondary',
                                };
                                $guaranteeLabel = match ($user->guaranteeLevel) {
                                    'low'         => 'Bajo',
                                    'substantial' => 'Sustancial',
                                    'high'        => 'Alto',
                                    default       => $user->guaranteeLevel,
                                };
                                ?>
                                <span class="badge <?= $guaranteeBadge ?> badge-lg">
                                    <?= $guaranteeLabel ?>
                                </span>
                                <small class="text-muted d-block m-t-5">
                                    <?php if ($user->guaranteeLevel === 'low'): ?>
                                        Seg&uacute;n eIDAS
                                    <?php elseif ($user->guaranteeLevel === 'substantial'): ?>
                                        eIDAS Sustancial
                                    <?php elseif ($user->guaranteeLevel === 'high'): ?>
                                        eIDAS Alto
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row clearfix">
                    <!-- ============================================
                         LEFT COLUMN — INFO
                         ============================================ -->
                    <div class="col-lg-4 col-md-12">
                        <!-- User info card -->
                        <div class="card">
                            <div class="header">
                                <h2><strong>Informaci&oacute;n</strong> personal</h2>
                                <ul class="header-dropdown">
                                    <li class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                            <i class="zmdi zmdi-more"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="<?= base_url('/profile/edit') ?>">Editar perfil</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            <div class="body">
                                <?php if ($user->identityType === 'physical'): ?>
                                <small class="text-muted">Nombre:</small>
                                <p><?= esc($user->firstName ?? '') ?> <?= esc($user->lastName ?? '') ?></p>
                                <hr>
                                <?php else: ?>
                                <small class="text-muted">Raz&oacute;n social:</small>
                                <p><?= esc($user->legalName ?? '') ?></p>
                                <hr>
                                <?php endif; ?>

                                <small class="text-muted">NIF/NIE:</small>
                                <p>
                                    <?php if (!empty($user->taxIdEncrypted)): ?>
                                        <span class="text-monospace">
                                            ***<?= esc(substr($user->taxIdEncrypted, -4)) ?>
                                        </span>
                                        <small class="text-muted">(enmascarado)</small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </p>
                                <hr>

                                <small class="text-muted">Email:</small>
                                <p>
                                    <?= esc($user->email) ?>
                                    <?php if ($user->isEmailVerified()): ?>
                                        <span class="badge badge-success m-l-10">
                                            <i class="zmdi zmdi-check-circle"></i> Verificado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning m-l-10">
                                            <i class="zmdi zmdi-alert-circle"></i> No verificado
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <hr>

                                <small class="text-muted">Tel&eacute;fono:</small>
                                <p><?= !empty($user->phone) ? esc($user->phone) : '<span class="text-muted">—</span>' ?></p>
                                <hr>

                                <small class="text-muted">Estado:</small>
                                <p>
                                    <?php
                                    $statusBadge = match ($user->status) {
                                        'active'    => 'badge-success',
                                        'inactive'  => 'badge-secondary',
                                        'suspended' => 'badge-warning',
                                        'blocked'   => 'badge-danger',
                                        default     => 'badge-secondary',
                                    };
                                    $statusLabel = match ($user->status) {
                                        'active'    => 'Activo',
                                        'inactive'  => 'Inactivo',
                                        'suspended' => 'Suspendido',
                                        'blocked'   => 'Bloqueado',
                                        default     => $user->status,
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
                                </p>
                                <hr>

                                <small class="text-muted">Miembro desde:</small>
                                <p><?= date('d/m/Y', strtotime((string) $user->createdAt)) ?></p>
                                <?php if ($user->lastLoginAt): ?>
                                <hr>
                                <small class="text-muted">&Uacute;ltimo acceso:</small>
                                <p><?= date('d/m/Y H:i', strtotime((string) $user->lastLoginAt)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- eIDAS Security info -->
                        <div class="card">
                            <div class="header">
                                <h2><strong>Seguridad</strong> eIDAS</h2>
                            </div>
                            <div class="body">
                                <p class="text-muted">
                                    El nivel de garant&iacute;a determina la confianza en la identidad del usuario
                                    seg&uacute;n el reglamento eIDAS (Reglamento UE n.&ordm; 910/2014).
                                </p>
                                <div class="m-b-15">
                                    <span class="badge badge-secondary">Bajo</span>
                                    <small class="text-muted d-block m-t-5">
                                        Identidad autodeclarada. Sin verificaci&oacute;n documental.
                                        &Uacute;til para pruebas o env&iacute;os informales.
                                    </small>
                                </div>
                                <div class="m-b-15">
                                    <span class="badge badge-info">Sustancial</span>
                                    <small class="text-muted d-block m-t-5">
                                        Identidad verificada con documento de identidad.
                                        Nivel equivalente a la identificaci&oacute;n presencial.
                                    </small>
                                </div>
                                <div class="m-b-0">
                                    <span class="badge badge-success">Alto</span>
                                    <small class="text-muted d-block m-t-5">
                                        Identidad verificada con documento + biometr&iacute;a o videollamada.
                                        M&aacute;ximo nivel de confianza eIDAS.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================
                         RIGHT COLUMN — TOTP + SESSIONS
                         ============================================ -->
                    <div class="col-lg-8 col-md-12">
                        <!-- TOTP Section -->
                        <div class="card">
                            <div class="header">
                                <h2><strong>Autenticaci&oacute;n</strong> de dos factores (TOTP)</h2>
                                <ul class="header-dropdown">
                                    <li class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                            <i class="zmdi zmdi-more"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <?php if ($user->hasTotpEnabled()): ?>
                                            <li><a href="<?= base_url('/profile/totp/disable') ?>">Deshabilitar TOTP</a></li>
                                            <li><a href="<?= base_url('/profile/totp/recovery-codes') ?>">Ver c&oacute;digos de recuperaci&oacute;n</a></li>
                                            <?php else: ?>
                                            <li><a href="<?= base_url('/profile/totp/enable') ?>">Habilitar TOTP</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            <div class="body">
                                <?php if ($user->hasTotpEnabled()): ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="alert alert-success m-b-0" role="alert">
                                            <i class="zmdi zmdi-check-circle"></i>
                                            <strong>TOTP activado.</strong>
                                            La autenticaci&oacute;n de dos factores est&aacute; habilitada en su cuenta.
                                            <?php if ($user->totpFailures > 0): ?>
                                            <br>
                                            <small class="text-warning">
                                                <i class="zmdi zmdi-alert-triangle"></i>
                                                <?= (int) $user->totpFailures ?> intento(s) fallido(s) desde el &uacute;ltimo inicio de sesi&oacute;n exitoso.
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <!-- QR code placeholder -->
                                        <div class="bg-light p-3 rounded">
                                            <i class="zmdi zmdi-smartphone zmdi-hc-4x text-muted"></i>
                                            <p class="m-b-0 text-muted small">
                                                <i class="zmdi zmdi-qrcode"></i>
                                                Escanee el c&oacute;digo QR en su app de autenticaci&oacute;n
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning m-b-0" role="alert">
                                    <i class="zmdi zmdi-alert-triangle"></i>
                                    <strong>TOTP no activado.</strong>
                                    La autenticaci&oacute;n de dos factores a&ntilde;ade una capa extra de seguridad.
                                    <a href="<?= base_url('/profile/totp/enable') ?>" class="alert-link">
                                        Habilitar ahora
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sessions / Devices Section -->
                        <div class="card">
                            <div class="header">
                                <h2><strong>Dispositivos</strong> activos</h2>
                                <ul class="header-dropdown">
                                    <li class="dropdown">
                                        <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                            <i class="zmdi zmdi-more"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="<?= base_url('/profile/devices') ?>">Gestionar dispositivos</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            <div class="body">
                                <?php if (!empty($devices)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover m-b-0">
                                        <thead>
                                            <tr>
                                                <th>Dispositivo</th>
                                                <th>Tipo</th>
                                                <th>Ultimo acceso</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($devices as $device): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= esc($device->deviceName) ?></strong>
                                                    <?php if ($device->browser || $device->operatingSystem): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= esc($device->browser ?? '') ?>
                                                        <?= ($device->browser && $device->operatingSystem) ? ' · ' : '' ?>
                                                        <?= esc($device->operatingSystem ?? '') ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $deviceIcon = match ($device->deviceType) {
                                                        'desktop' => 'zmdi zmdi-desktop-windows',
                                                        'laptop'  => 'zmdi zmdi-laptop',
                                                        'tablet'  => 'zmdi zmdi-tablet',
                                                        'mobile'  => 'zmdi zmdi-smartphone',
                                                        default   => 'zmdi zmdi-devices',
                                                    };
                                                    $deviceTypeLabel = match ($device->deviceType) {
                                                        'desktop' => 'Escritorio',
                                                        'laptop'  => 'Portatil',
                                                        'tablet'  => 'Tablet',
                                                        'mobile'  => 'Movil',
                                                        default   => $device->deviceType,
                                                    };
                                                    ?>
                                                    <i class="<?= $deviceIcon ?> m-r-5"></i>
                                                    <?= $deviceTypeLabel ?>
                                                </td>
                                                <td>
                                                    <?php if ($device->lastSeenAt): ?>
                                                        <?= date('d/m/Y H:i', strtotime((string) $device->lastSeenAt)) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-icon btn-neutral btn-icon-mini btn-revoke-device"
                                                            data-id="<?= esc($device->id) ?>"
                                                            data-name="<?= esc($device->deviceName) ?>"
                                                            title="Revocar dispositivo">
                                                        <i class="zmdi zmdi-close-circle"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="zmdi zmdi-devices zmdi-hc-4x text-muted m-b-10"></i>
                                    <p class="text-muted m-b-0">No hay dispositivos registrados.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Security Summary -->
                        <div class="card">
                            <div class="header">
                                <h2><strong>Resumen</strong> de seguridad</h2>
                            </div>
                            <div class="body">
                                <div class="row clearfix">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="info-box-3 bg-info">
                                            <div class="icon">
                                                <i class="zmdi zmdi-shield-check"></i>
                                            </div>
                                            <div class="content">
                                                <div class="text">Nivel eIDAS</div>
                                                <div class="number"><?= $guaranteeLabel ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="info-box-3 <?= $user->hasTotpEnabled() ? 'bg-success' : 'bg-warning' ?>">
                                            <div class="icon">
                                                <i class="zmdi zmdi-smartphone"></i>
                                            </div>
                                            <div class="content">
                                                <div class="text">TOTP</div>
                                                <div class="number"><?= $user->hasTotpEnabled() ? 'Activado' : 'Desactivado' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row clearfix m-t-10">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="info-box-3 bg-<?= $user->isEmailVerified() ? 'success' : 'warning' ?>">
                                            <div class="icon">
                                                <i class="zmdi zmdi-email"></i>
                                            </div>
                                            <div class="content">
                                                <div class="text">Email</div>
                                                <div class="number"><?= $user->isEmailVerified() ? 'Verificado' : 'No verificado' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="info-box-3 <?= $user->isActive() ? 'bg-success' : 'bg-danger' ?>">
                                            <div class="icon">
                                                <i class="zmdi zmdi-account-circle"></i>
                                            </div>
                                            <div class="content">
                                                <div class="text">Estado</div>
                                                <div class="number"><?= $statusLabel ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Revoke device modal -->
<div class="modal fade"
     id="revoke-device-modal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="revoke-device-modal-label"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="title" id="revoke-device-modal-label">Revocar dispositivo</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="revoke-device-modal-text">
                    &iquest;Est&aacute; seguro de que desea revocar este dispositivo?
                </p>
                <p class="text-muted">
                    El dispositivo perder&aacute; el acceso a su cuenta y deber&aacute; ser autorizado nuevamente.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-simple btn-round waves-effect" data-dismiss="modal">
                    Cancelar
                </button>
                <?= form_open('profile/devices/revoke', ['id' => 'revoke-device-form', 'method' => 'POST']) ?>
                <?= csrf_field() ?>
                <input type="hidden" name="device_id" id="revoke-device-id" value="">
                <button type="submit" class="btn btn-danger btn-round waves-effect">
                    <i class="zmdi zmdi-close-circle"></i>&nbsp;Revocar
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
 * Profile page — device revocation and interactivity.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
(function() {
    'use strict';

    // ── Revoke device modal ──
    $(document).on('click', '.btn-revoke-device', function() {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#revoke-device-id').val(id);
        $('#revoke-device-modal-text').html(
            '&iquest;Est&aacute; seguro de que desea revocar el dispositivo <strong>' +
            $('<span>').text(name).html() +
            '</strong>?'
        );
        $('#revoke-device-modal').modal('show');
    });

})();
</script>

<?= $this->endSection() ?>
