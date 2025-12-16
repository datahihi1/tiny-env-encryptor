# Tiny Env Encryptor

Tiny Env Encryptor is a simple PHP command-line tool that helps you securely manage your environment variables by encrypting and decrypting them using [TinyEnv](https://github.com/datahihi1/tiny-env).

## Requirements
- PHP 7.1 or higher
- Composer
- [TinyEnv](https://github.com/datahihi1/tiny-env)
## Installation

```bash
composer require datahihi1/tiny-env-encryptor:^1
```

## Usage
After installation, you can use the `tiny-env-encrypt` command-line tool.
### Generate Encryption Key
To generate a new encryption key, run:
```bash
vendor/bin/tiny-env-encrypt generate
```
This will create a key and display it. Store this key securely, as you'll need it for encryption and decryption. 

### Encrypt .env File
To encrypt your `.env` file, use the following command:
```bash
vendor/bin/tiny-env-encrypt encrypt <secret_key> .env [.env.encrypted] [key1 key2 ...]
- `<secret_key>`: The encryption key generated earlier.
- `.env`: The input file containing environment variables.
- `[.env.encrypted]`: (Optional) The output file to save the encrypted variables. If not provided, it will overwrite the input file.
- `[key1 key2 ...]`: (Optional) Specific keys to encrypt. If not provided, all variables will be encrypted.
```

Or helper:
```php
$value = encrypt_env($value, $secretKey);
```

### Decrypt .env File
To decrypt your encrypted `.env` file, use the following command:
```bash
vendor/bin/tiny-env-encrypt decrypt <secret_key> .env.encrypted [.env.decrypted]
- `<secret_key>`: The encryption key used for encryption.
- `.env.encrypted`: The input file containing encrypted environment variables.
- `[.env.decrypted]`: (Optional) The output file to save the decrypted variables. If not provided, it will overwrite the input file.
```

Or helper:
```php
$value = decrypt_env($value, $secretKey);
```

## License
This project is licensed under the MIT License. See the [LICENSE](https://github.com/datahihi1/tiny-env/blob/main/LICENSE) file for details.
