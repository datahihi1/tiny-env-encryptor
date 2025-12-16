<?php

if (!function_exists('encrypt_env')) {
    /**
     * Encrypt an environment variable value.
     *
     * @param string|null $value The plain text value to encrypt
     * @param string $secretKey The base64-encoded secret key used for encryption
     * @return string
     * @throws InvalidArgumentException if $value is null
     * @throws RuntimeException if encryption fails
     */
    function encrypt_env(?string $value, string $secretKey): string
    {
        if ($value === null) {
            throw new InvalidArgumentException('Value cannot be null');
        }

        $encrypter = new \Datahihi1\TinyEnv\Encryptor($secretKey);
        return $encrypter->encryptValue($value);
    }
}

if (!function_exists('decrypt_env')) {
    /**
     * Decrypt an encrypted environment variable value.
     *
     * @param string|null $value The encrypted value in the format "ENC(...)"
     * @param string $secretKey The base64-encoded secret key used for decryption
     * @param bool $flagIfFail Whether to throw an exception on failure
     * @return string
     * @throws InvalidArgumentException if $value is null
     * @throws RuntimeException if decryption fails and $flagIfFail is true
     */
    function decrypt_env(?string $value, string $secretKey, $flagIfFail = false): string
    {
        if ($value === null) {
            throw new InvalidArgumentException('Value cannot be null');
        }

        $decrypter = new \Datahihi1\TinyEnv\Decryptor($secretKey);
        return $decrypter->decrypt($value, $flagIfFail);
    }
}