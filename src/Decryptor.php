<?php

namespace Datahihi1\TinyEnv;

/**
 * Class Decryptor to decrypt environment variable values.
 *
 * Provides functionality to decrypt environment variable values.
 */
class Decryptor
{
    private $secretKey;

    /**
     * Constructor for Decryptor.
     *
     * @param string $secretKey The base64-encoded secret key used for decryption
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = base64_decode($secretKey);
    }

    /**
     * Decrypt an encrypted value.
     *
     * @param string $value The encrypted value in the format "ENC(...)"
     * @param bool $flagIfFail Whether to throw an exception on failure
     * @return string The decrypted value
     * @throws \RuntimeException if decryption fails and $flagIfFail is true
     */
    public function decrypt(string $value, bool $flagIfFail = false): string
    {
        if (!preg_match('/^ENC\((.*)\)$/', $value, $m)) {
            return $value;
        }

        $data = base64_decode($m[1]);

        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);

        $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', $this->secretKey, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false && $flagIfFail) {
            throw new \RuntimeException('Decryption failed.');
        }
        return $decrypted;
    }
}
