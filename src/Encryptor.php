<?php

namespace Datahihi1\TinyEnv;

/**
 * Class Encryptor to encrypt environment variable values.
 *
 * Provides functionality to encrypt environment variable values.
 */
class Encryptor
{
    private $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = base64_decode($secretKey);
    }

    public function generate(array $variables, array $encryptKeys = []): string
    {
        $lines = [];

        foreach ($variables as $key => $value) {
            if (in_array($key, $encryptKeys, true)) {
                $value = $this->encryptValue($value);
            }

            $lines[] = sprintf('%s=%s', $key, $value);
        }

        return implode(PHP_EOL, $lines);
    }

    public static function generateKey(): string
    {
        $static = new static(base64_encode(random_bytes(32)));
        return $static->secretKey;
    }

    private function encryptValue(string $plain): string
    {
        $iv = random_bytes(16);

        $cipher = openssl_encrypt(
            $plain,
            'AES-256-CBC',
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return "ENC(" . base64_encode($iv . $cipher) . ")";
    }
}
