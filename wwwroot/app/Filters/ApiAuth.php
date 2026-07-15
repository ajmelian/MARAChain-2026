<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiAuth filter — JSON 401 for unauthenticated API requests.
 *
 * Unlike SHIELD's session filter which redirects to /login,
 * this filter returns a JSON 401 response suitable for API consumers.
 *
 * Disabled automatically when ENVIRONMENT === 'testing'.
 *
 * @package App\Filters
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class ApiAuth implements FilterInterface
{
    /**
     * Check authentication for API requests.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|null
     *
     * @since 1.5.0
     */
    public function before(RequestInterface $request, $arguments = null): RequestInterface|ResponseInterface|null
    {
        if (ENVIRONMENT === 'testing') {
            return null;
        }

        if (! auth()->loggedIn()) {
            return service('response')->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Authentication required.',
            ]);
        }

        return null;
    }

    /**
     * No-op after filter.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
