<?php

declare(strict_types=1);

namespace Datahihi1\TinyEnv;

use RuntimeException;

/**
 * Class Decryptor to decrypt environment variable values.
 *
 * Provides functionality to decrypt environment variable values stored in the
 * `ENC(...)` envelope described by {@see EncryptedValue}.
 */
class Decryptor
{
    /** @var string Raw (base64-decoded) secret key bytes. */
    private $secretKey;

    /**
     * Constructor for Decryptor.
     *
     * @param string $secretKey The base64-encoded secret key used for decryption
     * @throws \InvalidArgumentException if the key is not valid base64 or has the wrong length
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = SecretKey::decode($secretKey);
    }

    /**
     * Decrypt an encrypted value.
     *
     * A value without the `ENC(...)` envelope is returned unchanged. When
     * decryption of an encrypted value fails, the original `ENC(...)` value is
     * returned so the ciphertext is never silently replaced by an empty
     * string; pass $flagIfFail to get an exception instead.
     *
     * @param string $value The encrypted value in the format "ENC(...)"
     * @param bool $flagIfFail Whether to throw an exception on failure
     * @return string The decrypted value
     * @throws RuntimeException if decryption fails and $flagIfFail is true
     */
    public function decrypt(string $value, bool $flagIfFail = false): string
    {
        if (!EncryptedValue::looksLikeEnvelope($value)) {
            return $value;
        }

        $payload = EncryptedValue::unwrap($value);
        $data = $payload === null ? false : base64_decode($payload, true);

        if ($data === false || strlen($data) <= EncryptedValue::IV_LENGTH) {
            return $this->failed($value, 'malformed encrypted value', $flagIfFail);
        }

        $decrypted = openssl_decrypt(
            substr($data, EncryptedValue::IV_LENGTH),
            EncryptedValue::CIPHER,
            $this->secretKey,
            OPENSSL_RAW_DATA,
            substr($data, 0, EncryptedValue::IV_LENGTH)
        );

        if ($decrypted === false) {
            return $this->failed($value, 'wrong key or corrupted data', $flagIfFail);
        }

        return $decrypted;
    }

    /**
     * Check whether a value is an `ENC(...)` encrypted value.
     *
     * @param string $value
     * @return bool
     */
    public static function isEncrypted(string $value): bool
    {
        return EncryptedValue::looksLikeEnvelope($value);
    }

    /**
     * Handle a failed decryption.
     *
     * @param string $value  The original encrypted value
     * @param string $reason Why decryption failed
     * @param bool   $flagIfFail Whether to throw instead of returning
     * @return string
     * @throws RuntimeException if $flagIfFail is true
     */
    private function failed(string $value, string $reason, bool $flagIfFail): string
    {
        if ($flagIfFail) {
            throw new RuntimeException('Decryption failed: ' . $reason . '.');
        }

        return $value;
    }
}
