<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

/**
 * Rate limiter filter for public endpoints.
 *
 * Uses file-based token bucket stored in writable/cache/.
 * Configurable per route group via constructor parameter:
 *   - auth: 6 attempts per minute (login, register)
 *   - api: 60 requests per minute (general API)
 *
 * @package App\Filters
 * @author  Aythami
 * @since   1.4.0
 */
class Throttle implements FilterInterface
{
    private int $maxAttempts;
    private int $decaySeconds;
    private string $prefix;

    /**
     * Supported throttle keys.
     *
     * @var array<string, array{max: int, decay: int}>
     */
    private const LIMITS = [
        'auth' => ['max' => 6, 'decay' => 60],
        'api'  => ['max' => 60, 'decay' => 60],
    ];

    /**
     * @param array|null $arguments Key name from LIMITS constant
     */
    public function __construct()
    {
        $this->maxAttempts  = 6;
        $this->decaySeconds = 60;
        $this->prefix       = 'throttle_';
    }

    /**
     * Inspect the request and rate-limit if needed.
     *
     * @param RequestInterface $request  Incoming request
     * @param array|null       $arguments Filter arguments (e.g. ['auth'])
     *
     * @return RequestInterface|ResponseInterface|null
     */
    public function before(RequestInterface $request, $arguments = null): RequestInterface|ResponseInterface|null
    {
        if (ENVIRONMENT === 'testing') {
            return null;
        }

        $key = $arguments[0] ?? 'api';

        if (isset(self::LIMITS[$key])) {
            $this->maxAttempts  = self::LIMITS[$key]['max'];
            $this->decaySeconds = self::LIMITS[$key]['decay'];
        }

        $fingerprint = $this->resolveRequestSignature($request);
        $cacheKey    = $this->prefix . $key . '_' . $fingerprint;
        $cachePath   = WRITEPATH . 'cache/' . $cacheKey;

        $attempts = $this->getAttempts($cachePath);

        if ($attempts >= $this->maxAttempts) {
            return service('response')->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'message' => 'Too many requests. Please try again in ' . $this->decaySeconds . ' seconds.',
                'retry_after' => $this->decaySeconds,
            ]);
        }

        $this->incrementAttempts($cachePath, $attempts);

        return null;
    }

    /**
     * No-op: rate limiting is only in the before phase.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }

    /**
     * Generate a unique fingerprint from the request.
     */
    private function resolveRequestSignature(RequestInterface $request): string
    {
        $ip = $request->getIPAddress();

        return sha1(($ip ?? '127.0.0.1') . '|' . $request->getPath());
    }

    /**
     * Read current attempt count from cache.
     */
    private function getAttempts(string $cachePath): int
    {
        if (! is_file($cachePath)) {
            return 0;
        }

        $content = @file_get_contents($cachePath);

        if ($content === false) {
            return 0;
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            return 0;
        }

        if (($data['expires_at'] ?? 0) < time()) {
            @unlink($cachePath);

            return 0;
        }

        return (int) ($data['attempts'] ?? 0);
    }

    /**
     * Increment the attempt counter and write back.
     */
    private function incrementAttempts(string $cachePath, int $current): void
    {
        $data = [
            'attempts'   => $current + 1,
            'expires_at' => time() + $this->decaySeconds,
        ];

        @file_put_contents($cachePath, json_encode($data), LOCK_EX);
    }
}
