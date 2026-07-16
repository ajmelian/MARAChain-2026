<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// ── Health check (smoke test for deploy) ────────────────────────
$routes->get('health', 'HealthController::index');

// ═════════════════════════════════════════════════════════════════════
//  AUTH ROUTES — Sin autenticacion (rate-limited)
// ═════════════════════════════════════════════════════════════════════

$routes->get('login', 'Web\AuthController::login', ['as' => 'login', 'filter' => 'throttle:auth']);
$routes->post('login', 'Web\AuthController::login', ['filter' => 'throttle:auth']);
$routes->get('register', 'Web\AuthController::register', ['as' => 'register', 'filter' => 'throttle:auth']);
$routes->post('register', 'Web\AuthController::register', ['filter' => 'throttle:auth']);
$routes->get('logout', 'Web\AuthController::logout', ['as' => 'logout']);

// ── FNMT Certificate Authentication ─────────────────────────────
$routes->get('auth/fnmt', 'Web\FnmtController::login');
$routes->get('auth/fnmt/totp-setup', 'Web\FnmtController::totpSetup');
$routes->post('auth/fnmt/totp-setup', 'Web\FnmtController::totpSetup', ['filter' => 'throttle:auth']);
$routes->get('auth/fnmt/totp-verify', 'Web\FnmtController::totpVerify');
$routes->post('auth/fnmt/totp-verify', 'Web\FnmtController::totpVerify', ['filter' => 'throttle:auth']);

// ═════════════════════════════════════════════════════════════════════
//  WEB ROUTES — Vistas HTML (protegidas con SHIELD session auth)
// ═════════════════════════════════════════════════════════════════════

$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('inbox', 'Web\TransfersController::inbox');
    $routes->get('outbox', 'Web\TransfersController::outbox');
    $routes->get('transfers/new', 'Web\TransfersController::new');
    $routes->post('transfers/(:segment)/accept', 'Web\TransfersController::accept/$1');
    $routes->post('transfers/(:segment)/reject', 'Web\TransfersController::reject/$1');

    $routes->get('profile', 'Web\ProfileController::index');

    $routes->get('totp/setup', 'Web\AuthController::totpSetup');

    $routes->get('web/contacts', 'Web\ContactsController::index');
    $routes->post('web/contacts', 'Web\ContactsController::store');
    $routes->get('web/contacts/(:segment)', 'Web\ContactsController::edit/$1');
    $routes->put('web/contacts/(:segment)', 'Web\ContactsController::update/$1');
    $routes->delete('web/contacts/(:segment)', 'Web\ContactsController::delete/$1');
});

// ═════════════════════════════════════════════════════════════════════
//  API ROUTES — JSON (protegidas con api-auth)
// ═════════════════════════════════════════════════════════════════════

$routes->group('', ['filter' => 'api-auth'], static function (RouteCollection $routes): void {

    // ── Evidence ────────────────────────────────────────────────
    $routes->get('evidence', 'EvidenceController::index');
    $routes->get('evidence/(:segment)', 'EvidenceController::show/$1');

    // ── Ledger ──────────────────────────────────────────────────
    $routes->get('ledger', 'LedgerController::index');
    $routes->get('ledger/verify', 'LedgerController::verify');
    $routes->get('ledger/(:segment)', 'LedgerController::show/$1');

    // ── Timestamps ──────────────────────────────────────────────
    $routes->get('timestamps/(:segment)/receipt', 'TimestampController::receipt/$1');

    // ── Contacts ────────────────────────────────────────────────
    $routes->get('contacts', 'ContactController::index');
    $routes->post('contacts', 'ContactController::create');
    $routes->get('contacts/(:segment)', 'ContactController::show/$1');
    $routes->put('contacts/(:segment)', 'ContactController::update/$1');
    $routes->delete('contacts/(:segment)', 'ContactController::delete/$1');

    // ── Notifications ───────────────────────────────────────────
    $routes->get('notifications', 'NotificationController::index');
    $routes->get('notifications/(:segment)', 'NotificationController::show/$1');

    // ── Users ───────────────────────────────────────────────────
    $routes->group('users', static function (RouteCollection $routes): void {
        $routes->get('/',               'UserController::index');
        $routes->get('(:segment)',      'UserController::show/$1');
        $routes->post('/',              'UserController::create');
        $routes->put('(:segment)',      'UserController::update/$1');
        $routes->delete('(:segment)',   'UserController::delete/$1');
        $routes->post('(:segment)/totp', 'UserController::enableTotp/$1');
    });

    // ── Devices ─────────────────────────────────────────────────
    $routes->group('devices', static function (RouteCollection $routes): void {
        $routes->get('/',               'DeviceController::index');
        $routes->get('(:segment)',      'DeviceController::show/$1');
        $routes->post('/',              'DeviceController::register');
        $routes->delete('(:segment)',   'DeviceController::revoke/$1');
    });

    // ── Documents ───────────────────────────────────────────────
    $routes->group('documents', static function (RouteCollection $routes): void {
        $routes->get('/',                'DocumentController::index');
        $routes->get('(:segment)',       'DocumentController::show/$1');
        $routes->post('/',               'DocumentController::create');
        $routes->post('upload',          'DocumentUploadController::upload');
        $routes->post('(:segment)/seal', 'DocumentController::seal/$1');
        $routes->delete('(:segment)',    'DocumentController::delete/$1');
    $routes->post('(:segment)/destroy', 'DocumentController::destroy/$1');
    });

    // ── Transfers ───────────────────────────────────────────────
    $routes->group('transfers', static function (RouteCollection $routes): void {
        $routes->get('/',                   'TransferController::index');
        $routes->get('sent',                'TransferController::outbox');
        $routes->get('received',            'TransferController::inbox');
        $routes->get('(:segment)',          'TransferController::show/$1');
        $routes->post('/',                  'TransferController::create');
        $routes->post('(:segment)/revoke',  'TransferController::revoke/$1');
        $routes->post('(:segment)/accept',  'TransferController::accept/$1');
        $routes->post('(:segment)/reject',  'TransferController::reject/$1');
    });

    // ── Signatures ──────────────────────────────────────────────
    $routes->group('signatures', static function (RouteCollection $routes): void {
        $routes->get('(:segment)',     'SignatureController::show/$1');
        $routes->post('/',             'SignatureController::request');
    });

}); // end api-auth group
