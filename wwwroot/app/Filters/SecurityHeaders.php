<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Headers Filter
 *
 * Applies security headers to every HTTP response in accordance with
 * OWASP Secure Headers Project and MARAChain security specification.
 *
 * Headers applied:
 *   - X-Content-Type-Options: nosniff
 *   - X-Frame-Options: DENY
 *   - X-XSS-Protection: 1; mode=block
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *   - Strict-Transport-Security: max-age=31536000; includeSubDomains
 *   - Content-Security-Policy: default-src 'self'
 *   - Permissions-Policy: camera=(), microphone=(), geolocation=()
 *
 * @package App\Filters
 * @author  MARAChain Team
 * @version 1.0.0
 * @since   2026-07-13
 *
 * @see .opencode/openspec/spec/security/auth.openspec.yaml
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders implements FilterInterface
{
    /**
     * Security headers map.
     *
     * @var array<string, string>
     */
    protected array $headers = [
        // https://owasp.org/www-project-secure-headers/#x-content-type-options
        'X-Content-Type-Options' => 'nosniff',

        // https://owasp.org/www-project-secure-headers/#x-frame-options
        'X-Frame-Options' => 'DENY',

        // https://owasp.org/www-project-secure-headers/#x-xss-protection
        'X-XSS-Protection' => '1; mode=block',

        // https://owasp.org/www-project-secure-headers/#referrer-policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // https://owasp.org/www-project-secure-headers/#strict-transport-security
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',

        // https://owasp.org/www-project-secure-headers/#content-security-policy
        'Content-Security-Policy' => "default-src 'self'",

        // https://owasp.org/www-project-secure-headers/#permissions-policy
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    /**
     * Before filter — no operation needed for security headers.
     *
     * @param RequestInterface       $request  Incoming request
     * @param list<string>|null      $arguments Optional filter arguments
     *
     * @return RequestInterface|ResponseInterface|string|null
     *
     * @since 1.0.0
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Security headers are applied in the after() method.
        return null;
    }

    /**
     * After filter — sets all required security headers on the response.
     *
     * Iterates over the $headers map and sets each header on the response
     * object. Returns the modified response for further processing.
     *
     * @param RequestInterface       $request  Incoming request
     * @param ResponseInterface      $response Outgoing response
     * @param list<string>|null      $arguments Optional filter arguments
     *
     * @return ResponseInterface|null
     *
     * @since 1.0.0
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        foreach ($this->headers as $header => $value) {
            $response->setHeader($header, $value);
        }

        return $response;
    }
}
