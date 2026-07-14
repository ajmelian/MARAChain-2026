<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * UUID v4 generator helper.
 *
 * Generates RFC 4122 compliant UUIDs version 4 using
 * cryptographically secure random bytes.
 *
 * @package App\Helpers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.4.0
 */
class Uuid
{
    /**
     * Generate a UUID v4 compatible with RFC 4122.
     *
     * Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * where x is any hex digit and y is one of [89ab].
     *
     * @return string UUID v4 in canonical 8-4-4-4-12 format
     *
     * @since 1.4.0
     */
    public static function v4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
