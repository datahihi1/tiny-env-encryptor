<?php

declare(strict_types=1);

namespace Datahihi1\TinyEnv;

use InvalidArgumentException;

/**
 * Validation and generation of the base64-encoded secret keys used by
 * {@see Encryptor} and {@see Decryptor}.
 *
 * A key is always exchanged with the outside world base64-encoded and must
 * decode to exactly {@see SecretKey::LENGTH} raw bytes, the key size required
 * by {@see EncryptedValue::CIPHER}.
 */
final class SecretKey
{
    /** Required raw key length in bytes (AES-256 => 32 bytes). */
    const LENGTH = 32;

    /**
     * Generate a new random key, base64-encoded and ready to be stored.
     *
     * @return string
     */
    public static function generate(): string
    {
        return base64_encode(random_bytes(self::LENGTH));
    }

    /**
     * Decode a base64-encoded key into its raw bytes.
     *
     * @param string $secretKey The base64-encoded key
     * @return string The raw key bytes
     * @throws InvalidArgumentException if the key is not valid base64 or has the wrong length
     */
    public static function decode(string $secretKey): string
    {
        $raw = base64_decode(trim($secretKey), true);

        if ($raw === false) {
            throw new InvalidArgumentException('Invalid secret key: must be base64 encoded.');
        }

        if (strlen($raw) !== self::LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Invalid secret key: must decode to %d bytes, got %d.',
                self::LENGTH,
                strlen($raw)
            ));
        }

        return $raw;
    }

    /**
     * Check whether a base64-encoded key is usable, without throwing.
     *
     * @param string $secretKey The base64-encoded key
     * @return bool
     */
    public static function isValid(string $secretKey): bool
    {
        try {
            self::decode($secretKey);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
