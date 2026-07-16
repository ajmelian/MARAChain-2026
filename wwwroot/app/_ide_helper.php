<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

/**
 * IDE Helper — CI4 global functions + SHIELD + ResponseTrait stubs.
 *
 * Sin namespace block, para compatibilidad con PHPStan e intelephense.
 * Las funciones helper de CI4 se declaran en el espacio global como stubs.
 *
 * @since 1.7.0
 */

// ═════════════════════════════════════════════════════════════════════
//  CI4 Global Helpers
// ═════════════════════════════════════════════════════════════════════

if (! function_exists('model')) {
    /**
     * @template T of object
     * @param class-string<T>|string $name
     * @return T|mixed|null
     */
    function model(string $name) {}
}

if (! function_exists('auth')) {
    /**
     * @return \CodeIgniter\Shield\Authentication\Authentication|null
     */
    function auth() {}
}

if (! function_exists('service')) {
    /**
     * @template T of object
     * @param class-string<T>|string $name
     * @param mixed ...$params
     * @return T|mixed|null
     */
    function service(string $name, ...$params) {}
}

if (! function_exists('view')) {
    /**
     * @param string $name
     * @param array  $data
     * @param array  $options
     * @return string
     */
    function view(string $name, array $data = [], array $options = []) {}
}

if (! function_exists('esc')) {
    /**
     * @param mixed  $value
     * @param string $charset
     * @return string
     */
    function esc(mixed $value, string $charset = 'UTF-8'): string {}
}

if (! function_exists('csrf_field')) {
    /**
     * @return string
     */
    function csrf_field(): string {}
}

if (! function_exists('redirect')) {
    /**
     * @param string|null $route
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function redirect(?string $route = null): \CodeIgniter\HTTP\RedirectResponse {}
}

if (! function_exists('config')) {
    /**
     * @template T of object
     * @param class-string<T>|string $name
     * @param array|null $settings
     * @return T|\CodeIgniter\Config\BaseConfig|null
     */
    function config(string $name, ?array $settings = null) {}
}

if (! function_exists('env')) {
    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    function env(?string $key = null, mixed $default = null): mixed {}
}

if (! function_exists('db_connect')) {
    /**
     * @param string|null $group
     * @param bool|null $getShared
     * @return \CodeIgniter\Database\BaseConnection
     */
    function db_connect(?string $group = null, ?bool $getShared = null) {}
}

if (! function_exists('log_message')) {
    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     * @return bool
     */
    function log_message(string $level, string $message, array $context = []): bool {}
}

if (! function_exists('session')) {
    /**
     * @param string|null $val
     * @param string|null $default
     * @return mixed
     */
    function session(?string $val = null, ?string $default = null) {}
}

if (! function_exists('form_open')) {
    /**
     * @param string $action
     * @param array  $attributes
     * @param array  $hidden
     * @return string
     */
    function form_open(string $action = '', array $attributes = [], array $hidden = []): string {}
}

if (! function_exists('form_close')) {
    /**
     * @param array $attributes
     * @return string
     */
    function form_close(array $attributes = []): string {}
}

if (! function_exists('base_url')) {
    /**
     * @param string|null $uri
     * @return string
     */
    function base_url(?string $uri = null): string {}
}

// ═════════════════════════════════════════════════════════════════════
//  CI4 RequestInterface — methods added by CI4 but unknown to intelephense
// ═════════════════════════════════════════════════════════════════════
