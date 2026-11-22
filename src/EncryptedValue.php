<?php

declare(strict_types=1);

namespace Datahihi1\TinyEnv;

/**
 * The on-disk format of an encrypted environment value.
 *
 * An encrypted value is stored as `ENC(<base64>)`, where the base64 payload is
 * the random initialisation vector followed by the raw ciphertext:
 *
 *     ENC( base64( iv[16] . aes-256-cbc(plaintext) ) )
 *
 * This class owns the envelope and the cipher parameters so that
 * {@see Encryptor} and {@see Decryptor} cannot drift apart.
 */
final class EncryptedValue
{
    /** Envelope prefix. */
    const PREFIX = 'ENC(';

    /** Envelope suffix. */
    const SUFFIX = ')';

    /** OpenSSL cipher used for value encryption. */
    const CIPHER = 'AES-256-CBC';

    /** Initialisation vector length in bytes, as required by CIPHER. */
    const IV_LENGTH = 16;

    /**
     * Wrap a base64 payload in the `ENC(...)` envelope.
     *
     * @param string $payload Base64-encoded iv + ciphertext
     * @return string
     */
    public static function wrap(string $payload): string
    {
        return self::PREFIX . $payload . self::SUFFIX;
    }

    /**
     * Check whether a value carries the `ENC(...)` envelope.
     *
     * This only inspects the envelope, not the payload, so a value with a
     * corrupted payload is still reported as encrypted; that lets callers
     * surface a decryption error instead of silently treating a damaged
     * secret as plain text.
     *
     * @param string $value
     * @return bool
     */
    public static function looksLikeEnvelope(string $value): bool
    {
        return strlen($value) >= strlen(self::PREFIX) + strlen(self::SUFFIX)
            && strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0
            && substr($value, -strlen(self::SUFFIX)) === self::SUFFIX;
    }

    /**
     * Extract the base64 payload from an `ENC(...)` envelope.
     *
     * @param string $value
     * @return string|null The base64 payload, or null if $value is not a well-formed envelope
     */
    public static function unwrap(string $value): ?string
    {
        if (!self::looksLikeEnvelope($value)) {
            return null;
        }

        $payload = substr($value, strlen(self::PREFIX), -strlen(self::SUFFIX));

        if ($payload !== '' && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $payload) !== 1) {
            return null;
        }

        return $payload;
    }
}
