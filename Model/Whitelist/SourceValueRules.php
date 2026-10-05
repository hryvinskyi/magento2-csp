<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\HashAlgorithm;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceKind;
use Magento\Framework\Phrase;

/**
 * What a whitelist entry's value may be, and its canonical form.
 *
 * A host value is a host or scheme source in ASCII; a hash value is the base64 digest alone. No keyword, `*`,
 * whitespace, quote, comma, semicolon or control character can reach a header through an entry.
 */
class SourceValueRules
{
    private const HOST_PARTS = '~^(?<scheme>[a-z][a-z0-9+.\-]*://)?(?<wildcard>\*\.)?(?<host>[^/:]+)(?<rest>.*)$~iu';

    /**
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(private readonly HostSourceParser $hostSourceParser)
    {
    }

    /**
     * Why the value cannot be saved as the given value type, or null when it can.
     *
     * @param string $valueType
     * @param string $algorithm Hash algorithm; used by hash values only
     * @param string $value
     * @return Phrase|null
     */
    public function valueError(string $valueType, string $algorithm, string $value): ?Phrase
    {
        return match (ValueType::tryFrom($valueType)) {
            ValueType::HOST => $this->hostError($value),
            ValueType::HASH => $this->hashError($algorithm, $value),
            null => __('Choose host or hash as the value type.'),
        };
    }

    /**
     * Canonical form of a host value: trimmed, scheme and host lower-cased, an internationalized host in punycode.
     *
     * @param string $value
     * @return string
     */
    public function normalizeHost(string $value): string
    {
        $value = trim($value);
        if (preg_match('/[^\x20-\x7e]/', $value) === 1 && preg_match(self::HOST_PARTS, $value, $parts) === 1) {
            $ascii = idn_to_ascii($parts['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (is_string($ascii)) {
                $value = $parts['scheme'] . $parts['wildcard'] . $ascii . $parts['rest'];
            }
        }
        if (SourceKind::of($value) === SourceKind::Scheme) {
            return strtolower($value);
        }
        $parsed = $this->hostSourceParser->parse($value);

        return $parsed === null ? $value : $this->hostSourceParser->compose($parsed);
    }

    /**
     * Canonical form of a hash value: the digest without quotes or algorithm prefix.
     *
     * @param string $value
     * @return string
     */
    public function normalizeHash(string $value): string
    {
        $value = trim(trim($value), "'");

        return (string)preg_replace('/^sha(256|384|512)-/i', '', $value);
    }

    /**
     * Why a host value cannot be whitelisted, or null when it can.
     *
     * @param string $value
     * @return Phrase|null
     */
    public function hostError(string $value): ?Phrase
    {
        if ($value === '') {
            return __('Enter a host or scheme source.');
        }
        if (preg_match('/[\s;,\'"\x00-\x1f\x7f]/', $value) === 1) {
            return __('A source may not contain spaces, quotes, commas, semicolons or control characters.');
        }
        if (preg_match('/[^\x20-\x7e]/', $value) === 1) {
            return __('"%1" contains characters a header cannot carry. Enter the host in its punycode form.', $value);
        }

        $kind = SourceKind::of($value);
        if ($kind === SourceKind::Scheme) {
            return null;
        }
        if ($kind !== SourceKind::Host && $kind !== SourceKind::Wildcard) {
            return __('Keywords, nonces and hashes cannot be whitelisted as a host.');
        }
        $parts = $this->hostSourceParser->parse($value);
        if ($parts === null) {
            return __('"%1" is not a valid host source.', $value);
        }
        if ($parts['host'] === '*') {
            return __('"%1" allows every host. Whitelist the hosts you need instead.', $value);
        }
        if ($this->hostSourceParser->isWildcardHost($parts) && substr_count($parts['host'], '.') < 2) {
            return __('"%1" allows a whole top-level domain. Use a wildcard under a domain you trust.', $value);
        }

        return null;
    }

    /**
     * Why a hash value cannot be whitelisted, or null when it can.
     *
     * @param string $algorithm
     * @param string $value
     * @return Phrase|null
     */
    public function hashError(string $algorithm, string $value): ?Phrase
    {
        $hashAlgorithm = HashAlgorithm::tryFrom($algorithm);
        if ($hashAlgorithm === null) {
            return __('Choose sha256, sha384 or sha512 as the hash algorithm.');
        }
        if (preg_match('~^[A-Za-z0-9+/]+={0,2}$~', $value) !== 1 || strlen($value) !== $hashAlgorithm->encodedLength()) {
            return __('"%1" is not a base64-encoded %2 digest.', $value, $hashAlgorithm->value);
        }

        return null;
    }
}
