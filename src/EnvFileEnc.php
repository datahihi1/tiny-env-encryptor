<?php

declare(strict_types=1);

namespace Datahihi1\TinyEnv;

/**
 * A structure-preserving reader/writer for `.env` files.
 *
 * {@see EnvFileEnc::parse()} turns the file into a list of entries and
 * {@see EnvFileEnc::format()} turns that list back into text. Comments, blank
 * lines, indentation, `export ` prefixes and inline comments all survive the
 * round trip, so a value can be rewritten in place without discarding the rest
 * of the file.
 *
 * Each entry is an array with a `type` of either:
 *  - {@see EnvFileEnc::TYPE_RAW}:  `['type', 'raw']` — blank line, comment, or a
 *    line that is not a `KEY=VALUE` assignment; reproduced verbatim.
 *  - {@see EnvFileEnc::TYPE_PAIR}: `['type', 'indent', 'export', 'key', 'value',
 *    'suffix', 'raw', 'original']` — an assignment. `value` is the decoded
 *    value (quotes removed and escape sequences resolved) and `suffix` is the
 *    untouched trailing text, typically an inline comment. `raw` and
 *    `original` record the line as it was read, so {@see EnvFileEnc::format()}
 *    can reproduce an unmodified line byte-for-byte; change `value` and the
 *    line is re-encoded.
 */
final class EnvFileEnc
{
    /** A line reproduced verbatim (blank, comment, or unparsable). */
    const TYPE_RAW = 'raw';

    /** A `KEY=VALUE` assignment. */
    const TYPE_PAIR = 'pair';

    /**
     * Characters that force a value to be double-quoted on output.
     *
     * Whitespace and `#` would otherwise change how the value re-parses;
     * quotes, backslashes, `$` and backticks would otherwise be interpreted
     * by the reader or by a shell sourcing the file.
     */
    const NEEDS_QUOTING = '/[\s"\'\\\\$`#]/';

    /**
     * Detect the dominant line ending of a file's contents.
     *
     * Lets {@see EnvFileEnc::format()} write a Windows checkout back with CRLF
     * instead of silently rewriting every line of the file.
     *
     * @param string $contents Raw file contents
     * @return string "\r\n" or "\n"
     */
    public static function detectEol(string $contents): string
    {
        $crlf = substr_count($contents, "\r\n");

        return $crlf > 0 && $crlf >= substr_count($contents, "\n") - $crlf ? "\r\n" : "\n";
    }

    /**
     * Parse the contents of a `.env` file into a list of entries.
     *
     * @param string $contents Raw file contents
     * @return array<int, array<string, mixed>>
     */
    public static function parse(string $contents): array
    {
        if (strncmp($contents, "\xEF\xBB\xBF", 3) === 0) {
            $contents = substr($contents, 3);
        }

        $lines = preg_split("/\r\n|\n|\r/", $contents);
        if ($lines === false) {
            return [];
        }

        if ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        $entries = [];
        foreach ($lines as $line) {
            $entries[] = self::parseLine($line);
        }

        return $entries;
    }

    /**
     * Render a list of entries back into `.env` file contents.
     *
     * @param array<int, array<string, mixed>> $entries
     * @param string|null                      $eol Line ending to use; defaults to PHP_EOL
     * @return string
     */
    public static function format(array $entries, ?string $eol = null): string
    {
        $eol = $eol ?? PHP_EOL;
        $out = '';

        foreach ($entries as $entry) {
            if (($entry['type'] ?? self::TYPE_RAW) !== self::TYPE_PAIR) {
                $out .= ($entry['raw'] ?? '') . $eol;
                continue;
            }

            if (array_key_exists('raw', $entry) && $entry['value'] === ($entry['original'] ?? null)) {
                $out .= $entry['raw'] . $eol;
                continue;
            }

            $out .= ($entry['indent'] ?? '')
                . (($entry['export'] ?? false) ? 'export ' : '')
                . $entry['key']
                . '='
                . self::encode((string) $entry['value'])
                . ($entry['suffix'] ?? '')
                . $eol;
        }

        return $out;
    }

    /**
     * Reduce a list of entries to a `key => value` map.
     *
     * Later assignments win, matching how `.env` readers treat duplicate keys.
     *
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, string>
     */
    public static function toArray(array $entries): array
    {
        $variables = [];

        foreach ($entries as $entry) {
            if (($entry['type'] ?? null) === self::TYPE_PAIR) {
                $variables[$entry['key']] = (string) $entry['value'];
            }
        }

        return $variables;
    }

    /**
     * Encode a value for writing, quoting and escaping it only when needed.
     *
     * @param string $value
     * @return string
     */
    public static function encode(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match(self::NEEDS_QUOTING, $value) !== 1) {
            return $value;
        }

        return '"' . str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $value
        ) . '"';
    }

    /**
     * Parse a single line into an entry.
     *
     * @param string $line
     * @return array<string, mixed>
     */
    private static function parseLine(string $line): array
    {
        $trimmed = ltrim($line, " \t");

        if ($trimmed === '' || $trimmed[0] === '#') {
            return ['type' => self::TYPE_RAW, 'raw' => $line];
        }

        if (preg_match('/^([ \t]*)(export[ \t]+)?([^\s=#][^=]*?)[ \t]*=(.*)$/', $line, $m) !== 1) {
            return ['type' => self::TYPE_RAW, 'raw' => $line];
        }

        list($value, $suffix) = self::parseValue($m[4]);

        return [
            'type'     => self::TYPE_PAIR,
            'indent'   => $m[1],
            'export'   => $m[2] !== '',
            'key'      => $m[3],
            'value'    => $value,
            'suffix'   => $suffix,
            'raw'      => $line,
            'original' => $value,
        ];
    }

    /**
     * Split the text after `=` into a decoded value and an untouched suffix.
     *
     * @param string $rest Everything after the first `=` on the line
     * @return array{0: string, 1: string}
     */
    private static function parseValue(string $rest): array
    {
        $offset = strspn($rest, " \t");
        $quote = $rest[$offset] ?? '';

        if ($quote !== '"' && $quote !== "'") {
            return self::parseBareValue($rest);
        }

        $length = strlen($rest);
        $value = '';
        $i = $offset + 1;

        while ($i < $length) {
            $char = $rest[$i];

            if ($quote === '"' && $char === '\\' && $i + 1 < $length) {
                $value .= self::unescape($rest[$i + 1]);
                $i += 2;
                continue;
            }

            if ($char === $quote) {
                return [$value, substr($rest, $i + 1)];
            }

            $value .= $char;
            $i++;
        }

        return self::parseBareValue($rest);
    }

    /**
     * Parse an unquoted value, splitting off a whitespace-preceded inline comment.
     *
     * @param string $rest
     * @return array{0: string, 1: string}
     */
    private static function parseBareValue(string $rest): array
    {
        $rest = ltrim($rest, " \t");

        if (preg_match('/[ \t]+#/', $rest, $m, PREG_OFFSET_CAPTURE) === 1) {
            $cut = $m[0][1];

            return [substr($rest, 0, $cut), substr($rest, $cut)];
        }

        return [rtrim($rest, " \t"), ''];
    }

    /**
     * Resolve one escape sequence character.
     *
     * @param string $char The character following a backslash
     * @return string
     */
    private static function unescape(string $char): string
    {
        switch ($char) {
            case 'n':
                return "\n";
            case 'r':
                return "\r";
            case 't':
                return "\t";
            case '0':
                return "\0";
            case '\\':
            case '"':
            case '\'':
            case '$':
                return $char;
            default:
                return '\\' . $char;
        }
    }
}
