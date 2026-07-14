<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>MARAChain - <?= esc($title ?? 'Plataforma') ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/color_skins.css">
    <?= $this->renderSection('styles') ?>
</head>
<body class="theme-black">

<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img src="/assets/images/logo.svg" width="48" height="48" alt="MARAChain"></div>
        <p>Cargando...</p>
    </div>
</div>

<div class="overlay"></div>

<?php
    $loggedIn   = auth()->loggedIn();
    $authUser   = $loggedIn ? auth()->user() : null;
    $username   = $authUser ? $authUser->username : '';
    $userEmail  = $authUser ? $authUser->email : '';
    $userAvatar = substr(strtoupper($username ?: $userEmail ?: 'U'), 0, 1);
?>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="javascript:void(0);" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                <i class="zmdi zmdi-menu"></i>
            </a>
            <a href="javascript:void(0);" class="bars"></a>
            <a class="navbar-brand" href="/">MARAChain</a>
        </div>
        <div class="collapse navbar-collapse" id="navbar-collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php if ($loggedIn) : ?>
                <li>
                    <a href="/inbox" class="<?= (($current ?? '') === 'inbox') ? 'active' : '' ?>">
                        <i class="zmdi zmdi-email"></i> <span>Recibidos</span>
                    </a>
                </li>
                <li>
                    <a href="/outbox" class="<?= (($current ?? '') === 'outbox') ? 'active' : '' ?>">
                        <i class="zmdi zmdi-mail-send"></i> <span>Enviados</span>
                    </a>
                </li>
                <li>
                    <a href="/transfers/new" class="<?= (($current ?? '') === 'new') ? 'active' : '' ?>">
                        <i class="zmdi zmdi-plus"></i> <span>Nuevo envio</span>
                    </a>
                </li>
                <li>
                    <a href="/web/contacts">
                        <i class="zmdi zmdi-accounts-list"></i> <span>Contactos</span>
                    </a>
                </li>
                <li>
                    <a href="/profile">
                        <i class="zmdi zmdi-account"></i>
                        <span>
                            <?= esc($username ?: $userEmail) ?>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="/logout" class="text-danger">
                        <i class="zmdi zmdi-power"></i> <span>Cerrar sesion</span>
                    </a>
                </li>
                <?php else : ?>
                <li>
                    <a href="/login" class="<?= (($current ?? '') === 'login') ? 'active' : '' ?>">
                        <i class="zmdi zmdi-sign-in"></i> <span>Iniciar sesion</span>
                    </a>
                </li>
                <li>
                    <a href="/register" class="<?= (($current ?? '') === 'register') ? 'active' : '' ?>">
                        <i class="zmdi zmdi-account-add"></i> <span>Crear cuenta</span>
                    </a>
                </li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<section class="content">
    <div class="container-fluid">
        <?= $this->renderSection('content') ?>
    </div>
</section>

<!-- Scripts -->
<script src="/assets/plugins/jquery/jquery-v3.3.1.min.js"></script>
<script src="/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/bundles/libscripts.bundle.js"></script>
<script src="/assets/bundles/vendorscripts.bundle.js"></script>
<script src="/assets/bundles/mainscripts.bundle.js"></script>

<!-- MARAChain client-side scripts -->
<script src="/assets/js/marachain-crypto.js"></script>
<script src="/assets/js/marachain-validation.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        MARAValidation.init();
    });
</script>

<?= $this->renderSection('scripts') ?>
</body>
</html>
