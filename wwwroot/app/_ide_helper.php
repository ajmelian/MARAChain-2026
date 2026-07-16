<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

/**
 * IDE Helper — CI4 global functions + SHIELD + ResponseTrait stubs.
 *
 * Este archivo no tiene efecto en ejecución. Solo sirve para que
 * intelephense (LSP del editor) reconozca las funciones globales
 * de CodeIgniter 4 y SHIELD que no están declaradas en el código fuente.
 *
 * @since 1.7.0
 */

// ═════════════════════════════════════════════════════════════════════
//  CI4 Global Helpers
// ═════════════════════════════════════════════════════════════════════

/**
 * @template T of object
 * @param class-string<T>|string $name
 * @return T|mixed|null
 */
function model(string $name) {}

/**
 * @return \CodeIgniter\Shield\Authentication\Authentication|null
 */
function auth() {}

/**
 * @template T of object
 * @param class-string<T>|string $name
 * @return T|mixed|null
 */
function service(string $name, ...$params) {}

/**
 * @param string $name
 * @param array  $data
 * @param array  $options
 * @return string
 */
function view(string $name, array $data = [], array $options = []) {}

/**
 * @param mixed $value
 * @param string $charset
 * @return string
 */
function esc(mixed $value, string $charset = 'UTF-8'): string {}

/**
 * @return string
 */
function csrf_field(): string {}

/**
 * @return \CodeIgniter\HTTP\RedirectResponse
 */
function redirect(?string $route = null): \CodeIgniter\HTTP\RedirectResponse {}

/**
 * @template T of object
 * @param class-string<T>|string $name
 * @param array|null $settings
 * @return T|\CodeIgniter\Config\BaseConfig|null
 */
function config(string $name, ?array $settings = null) {}

/**
 * @param string|null $key
 * @param mixed|null $default
 * @return mixed
 */
function env(?string $key = null, mixed $default = null): mixed {}

/**
 * @param string|null $group
 * @param bool|null $getShared
 * @return \CodeIgniter\Database\BaseConnection&\CodeIgniter\Database\ConnectionInterface
 */
function db_connect(?string $group = null, ?bool $getShared = null) {}

/**
 * @param string $level
 * @param string $message
 * @param array $context
 * @return bool
 */
function log_message(string $level, string $message, array $context = []): bool {}

/**
 * @param string|null $val
 * @param string|null $default
 * @return mixed
 */
function session(?string $val = null, ?string $default = null) {}

// ═════════════════════════════════════════════════════════════════════
//  Form Helpers
// ═════════════════════════════════════════════════════════════════════

/**
 * @param string $action
 * @param array  $attributes
 * @param array  $hidden
 * @return string
 */
function form_open(string $action = '', array $attributes = [], array $hidden = []): string {}

/**
 * @param array  $attributes
 * @return string
 */
function form_close(array $attributes = []): string {}

// ═════════════════════════════════════════════════════════════════════
//  CI4 URL Helpers
// ═════════════════════════════════════════════════════════════════════

/**
 * @param string|null $uri
 * @return string
 */
function base_url(?string $uri = null): string {}

// ═════════════════════════════════════════════════════════════════════
//  CodeIgniter\Shield\Authentication\Authentication
// ═════════════════════════════════════════════════════════════════════

namespace CodeIgniter\Shield\Authentication {

    /**
     * @method bool loggedIn()
     * @method \CodeIgniter\Shield\Entities\User|null user()
     * @method \CodeIgniter\Shield\Authentication\AuthenticationResult attempt(array $credentials, bool $remember = false)
     * @method bool logout()
     * @method bool remember(bool $remember = false)
     * @method \CodeIgniter\Shield\Authorization\Groups groups()
     * @method \CodeIgniter\Shield\Models\UserModel getProvider()
     */
    class Authentication {}
}

// ═════════════════════════════════════════════════════════════════════
//  CodeIgniter\API\ResponseTrait — métodos comunes en controladores
// ═════════════════════════════════════════════════════════════════════

namespace CodeIgniter\API {

    trait ResponseTrait
    {
        /**
         * @param mixed|null $data
         * @param int $statusCode
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function respond(mixed $data = null, int $statusCode = 200, string $message = '') {}

        /**
         * @param mixed $data
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function respondCreated(mixed $data = null, string $message = '') {}

        /**
         * @param mixed $data
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function respondDeleted(mixed $data = null, string $message = '') {}

        /**
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function respondNoContent(string $message = '') {}

        /**
         * @param mixed $errors
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function failValidationErrors(mixed $errors, string $message = '') {}

        /**
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function failNotFound(string $message = '') {}

        /**
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function failUnauthorized(string $message = '') {}

        /**
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function failForbidden(string $message = '') {}

        /**
         * @param mixed $data
         * @param int $statusCode
         * @param string $message
         * @return \CodeIgniter\HTTP\ResponseInterface
         */
        public function fail(mixed $data = null, int $statusCode = 400, string $message = '') {}
    }
}

// ═════════════════════════════════════════════════════════════════════
//  CodeIgniter\Controller — propiedades de request/validator
// ═════════════════════════════════════════════════════════════════════

namespace CodeIgniter {
    class Controller
    {
        /** @var \CodeIgniter\HTTP\IncomingRequest */
        protected $request;

        /** @var \CodeIgniter\HTTP\ResponseInterface */
        protected $response;

        /** @var \CodeIgniter\Validation\Validation|null */
        protected $validator;
    }
}

// ═════════════════════════════════════════════════════════════════════
//  CodeIgniter\Entity\Exceptions\CastException (LSP false positives)
// ═════════════════════════════════════════════════════════════════════

namespace CodeIgniter\Entity\Exceptions {
    class CastException extends \RuntimeException {}
}
