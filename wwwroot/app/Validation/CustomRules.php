<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Custom validation rules for MARAChain.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CustomRules
{
    /**
     * Validate Spanish tax ID format (NIF/NIE/CIF).
     *
     * NIF: 8 digits + 1 letter
     * NIE: X/Y/Z + 7 digits + 1 letter
     * CIF: 1 letter + 7 digits + 1 digit/letter
     */
    public function valid_tax_id(string $value, ?string &$error = null): bool
    {
        $value = strtoupper(trim($value));

        if (preg_match('/^[0-9]{8}[A-Z]$/', $value)) {
            return true;
        }

        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $value)) {
            return true;
        }

        if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $value)) {
            return true;
        }

        $error = 'The {field} must be a valid Spanish NIF/NIE/CIF.';

        return false;
    }

    /**
     * Validate phone number in E.164 format.
     *
     * @param string      $value Phone number
     * @param string|null $error Error message reference
     */
    public function valid_phone_e164(string $value, ?string &$error = null): bool
    {
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
            return true;
        }

        $error = 'The {field} must be a valid phone number in E.164 format.';

        return false;
    }

    /**
     * Validate hexadecimal string of exact length (e.g., SHA-256 hash).
     */
    public function valid_hex(string $value, int $length = 64, ?string &$error = null): bool
    {
        if (strlen($value) === $length && ctype_xdigit($value)) {
            return true;
        }

        $error = 'The {field} must be a ' . $length . '-character hexadecimal string.';

        return false;
    }

    /**
     * Validate UUID v4 format.
     */
    public function valid_uuid(string $value, ?string &$error = null): bool
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            return true;
        }

        $error = 'The {field} must be a valid UUID v4.';

        return false;
    }
}
