<?php

declare(strict_types=1);

namespace Datahihi1\TinyEnv;

use RuntimeException;

/**
 * Class Encryptor to encrypt environment variable values.
 *
 * Provides functionality to encrypt environment variable values into the
 * `ENC(...)` envelope described by {@see EncryptedValue}.
 */
class Encryptor
{
    /** @var string Raw (base64-decoded) secret key bytes. */
    private $secretKey;

    /**
     * Constructor for Encryptor.
     *
     * @param string $secretKey The base64-encoded secret key used for encryption
     * @throws \InvalidArgumentException if the key is not valid base64 or has the wrong length
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = SecretKey::decode($secretKey);
    }

    /**
     * Generate a new random secret key.
     *
     * The returned key is base64-encoded, i.e. in the same form the
     * constructor expects.
     *
     * @return string
     */
    public static function generateKey(): string
    {
        return SecretKey::generate();
    }

    /**
     * Render environment variables as `.env` lines, encrypting the selected keys.
     *
     * Values that already carry the `ENC(...)` envelope are left untouched so
     * that running this twice cannot double-encrypt them.
     *
     * Note: an empty $encryptKeys means "encrypt nothing"; pass the keys you
     * want encrypted, or `array_keys($variables)` for all of them.
     *
     * @param array<string, string|int|float|bool|null> $variables   The variables to render
     * @param array<int, string>                        $encryptKeys The keys whose values to encrypt
     * @return string The `.env` content, without a trailing newline
     * @throws RuntimeException if encryption fails
     */
    public function generate(array $variables, array $encryptKeys = []): string
    {
        $selected = $encryptKeys === [] ? [] : array_fill_keys($encryptKeys, true);

        $lines = [];

        foreach ($variables as $key => $value) {
            $value = (string) $value;

            if (isset($selected[$key]) && !EncryptedValue::looksLikeEnvelope($value)) {
                $value = $this->encryptValue($value);
            }

            $lines[] = $key . '=' . EnvFileEnc::encode($value);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Encrypt a single plain value.
     *
     * @param string $plain The plain text value to encrypt
     * @return string The value wrapped as `ENC(...)`
     * @throws RuntimeException if encryption fails
     */
    public function encryptValue(string $plain): string
    {
        $iv = random_bytes(EncryptedValue::IV_LENGTH);

        $cipher = openssl_encrypt(
            $plain,
            EncryptedValue::CIPHER,
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipher === false) {
            throw new RuntimeException(
                'Encryption failed: ' . (openssl_error_string() ?: 'unknown OpenSSL error') . '.'
            );
        }

        return EncryptedValue::wrap(base64_encode($iv . $cipher));
    }
}
