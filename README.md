# Tiny Env Encryptor

Tiny Env Encryptor is a simple PHP command-line tool that helps you securely manage your environment variables by encrypting and decrypting them using [TinyEnv](https://github.com/datahihi1/tiny-env).

## Requirements
- PHP 7.1 or higher
- The `openssl` extension
- Composer
- [TinyEnv](https://github.com/datahihi1/tiny-env)

## Installation

```bash
composer require datahihi1/tiny-env-encryptor
```

## Usage

After installation, you can use the `tiny-env-encrypt` command-line tool.

```
tiny-env-encrypt generate                                              Generate a new encryption key
tiny-env-encrypt encrypt <key> <input.env> [output.env] [key1 key2 ...]  Encrypt .env values
tiny-env-encrypt decrypt <key> <input.env> [output.env] [key1 key2 ...]  Decrypt .env values
```

Run `tiny-env-encrypt help` for the full help text.

### Generate Encryption Key

To generate a new encryption key, run:

```bash
vendor/bin/tiny-env-encrypt generate
```

This prints a base64-encoded 32-byte key. Store it securely, as you'll need it
for encryption and decryption.

### Encrypt .env File

```bash
vendor/bin/tiny-env-encrypt encrypt <secret_key> .env [.env.encrypted] [key1 key2 ...]
```

- `<secret_key>`: The encryption key generated earlier.
- `.env`: The input file containing environment variables.
- `[.env.encrypted]`: (Optional) The output file. If not provided, the input file is rewritten in place.
- `[key1 key2 ...]`: (Optional) Specific keys to encrypt. If not provided, all variables are encrypted.

Or helper:

```php
$value = encrypt_env($value, $secretKey);
```

### Decrypt .env File

```bash
vendor/bin/tiny-env-encrypt decrypt <secret_key> .env.encrypted [.env.decrypted] [key1 key2 ...]
```

- `<secret_key>`: The encryption key used for encryption.
- `.env.encrypted`: The input file containing encrypted environment variables.
- `[.env.decrypted]`: (Optional) The output file. If not provided, `<input>.decrypted` is used.
- `[key1 key2 ...]`: (Optional) Specific keys to decrypt. If not provided, all encrypted variables are decrypted.

Or helper:

```php
$value = decrypt_env($value, $secretKey);
```

### Keeping the key off the command line

Anything you type on the command line ends up in your shell history and in the
process list. Pass `-` instead of the key to read it from the `TINY_ENV_KEY`
environment variable, or from standard input if that variable is not set:

```bash
TINY_ENV_KEY="$(cat key.txt)" vendor/bin/tiny-env-encrypt encrypt - .env .env.encrypted
cat key.txt | vendor/bin/tiny-env-encrypt decrypt - .env.encrypted .env
```

## Behaviour

- **The file's layout is preserved.** Comments, blank lines, indentation,
  `export ` prefixes, quoting style, inline comments and line endings all
  survive the round trip, so encrypting in place produces a diff that touches
  only the values you encrypted.
- **Only the keys you ask for are touched.** Variables you do not list are
  copied through unchanged instead of being dropped from the output.
- **Values are never encrypted twice.** A value already stored as `ENC(...)` is
  left alone and reported as skipped.
- **`${VAR}` references are resolved before encryption**, from the file's own
  variables first and then from the loaded environment, so the real value is
  what gets encrypted. References in values you do not encrypt are left as-is.
- **A failed decryption is fatal.** If the key is wrong or the ciphertext is
  damaged, the command reports which variable failed, writes nothing and exits
  non-zero, rather than replacing your secret with an empty value.
- **Exit status** is `0` on success and `1` on any error, so the commands can be
  used in scripts and CI.
- Output files created by the tool are given `0600` permissions. Existing files
  keep the permissions you gave them.

## Helper functions

The package registers four global helpers:

```php
$key   = generate_env_key();                  // new base64-encoded key
$value = encrypt_env('s3cr3t', $key);         // "ENC(...)"
$plain = decrypt_env($value, $key);           // "s3cr3t"
$plain = decrypt_env($value, $key, true);     // throw RuntimeException on failure
is_encrypted_env($value);                     // true for "ENC(...)" values
```

`encrypt_env()` and `decrypt_env()` throw `InvalidArgumentException` for a null
value or an unusable key. `decrypt_env()` returns a non-`ENC(...)` value
unchanged, and returns the original value if decryption fails unless you pass
`true` as the third argument.

## Security notes

- Values are encrypted with AES-256-CBC using a random IV per value, so the same
  plaintext never produces the same ciphertext twice.
- The key must be a base64-encoded 32-byte key. Shorter keys, longer keys and
  non-base64 input are rejected rather than silently padded.
- The format is not authenticated: it protects confidentiality, not integrity.
  Anyone who can edit the encrypted file can tamper with ciphertext, and the
  change will surface as a decryption failure or as corrupted plaintext rather
  than as an explicit tamper warning. Keep the encrypted file under version
  control or other change control, and never commit the key alongside it.

## License
This project is licensed under the MIT License. See the [LICENSE](https://github.com/datahihi1/tiny-env/blob/main/LICENSE) file for details.
