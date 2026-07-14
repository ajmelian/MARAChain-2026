<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Convert array keys from camelCase to snake_case.
     *
     * Used to normalise incoming JSON body keys before passing them
     * through CI4 validation rules (which are defined in snake_case).
     *
     * @param array<string, mixed> $input Associative array with camelCase keys
     *
     * @return array<string, mixed> Same values keyed by snake_case
     *
     * @since 1.1.1
     */
    protected function camelToSnake(array $input): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            $snakeKey = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            $result[$snakeKey] = $value;
        }

        return $result;
    }

    /**
     * Validate data against a named rules group from Config\Validation.
     *
     * @param array<string, mixed> $data  Data to validate
     * @param string               $group Group name in Config\Validation
     *
     * @return bool True if valid, false otherwise
     *
     * @since 1.1.1
     */
    protected function validateGroup(array $data, string $group): bool
    {
        $config = new \Config\Validation();

        if (! isset($config->{$group})) {
            return false;
        }

        return $this->validateData($data, $config->{$group});
    }
}
