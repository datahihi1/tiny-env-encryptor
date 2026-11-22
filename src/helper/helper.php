<?php

use \Datahihi1\TinyEnv\Encryptor;
use \Datahihi1\TinyEnv\Decryptor;
use \Datahihi1\TinyEnv\SecretKey;
use \Datahihi1\TinyEnv\EncryptedValue;

if (!function_exists('encrypt_env')) {
    /**
     * Encrypt an environment variable value.
     *
     * @param string|null $value The plain text value to encrypt
     * @param string $secretKey The base64-encoded secret key used for encryption
     * @return string The value wrapped as "ENC(...)"
     * @throws InvalidArgumentException if $value is null or $secretKey is not a valid key
     * @throws RuntimeException if encryption fails
     */
    function encrypt_env(?string $value, string $secretKey): string
    {
        if ($value === null) {
            throw new InvalidArgumentException('Value cannot be null');
        }

        $encryptor = new Encryptor($secretKey);

        return $encryptor->encryptValue($value);
    }
}

if (!function_exists('decrypt_env')) {
    /**
     * Decrypt an encrypted environment variable value.
     *
     * A value that is not in the "ENC(...)" format is returned unchanged.
     *
     * @param string|null $value The encrypted value in the format "ENC(...)"
     * @param string $secretKey The base64-encoded secret key used for decryption
     * @param bool $flagIfFail Whether to throw an exception on failure
     * @return string The decrypted value
     * @throws InvalidArgumentException if $value is null or $secretKey is not a valid key
     * @throws RuntimeException if decryption fails and $flagIfFail is true
     */
    function decrypt_env(?string $value, string $secretKey, bool $flagIfFail = false): string
    {
        if ($value === null) {
            throw new InvalidArgumentException('Value cannot be null');
        }

        $decryptor = new Decryptor($secretKey);

        return $decryptor->decrypt($value, $flagIfFail);
    }
}

if (!function_exists('is_encrypted_env')) {
    /**
     * Check whether a value is stored in the "ENC(...)" encrypted format.
     *
     * @param string|null $value
     * @return bool
     */
    function is_encrypted_env(?string $value): bool
    {
        return $value !== null && EncryptedValue::looksLikeEnvelope($value);
    }
}

if (!function_exists('generate_env_key')) {
    /**
     * Generate a new base64-encoded secret key for encrypt_env()/decrypt_env().
     *
     * @return string
     */
    function generate_env_key(): string
    {
        return SecretKey::generate();
    }
}
