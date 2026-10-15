<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Api\Data\ViolationInterfaceFactory;

/**
 * Makes a violation safe to store: URLs lose their query and fragment, every text fits its column, characters
 * outside the Basic Multilingual Plane are replaced.
 */
class ViolationSanitizer
{
    private const FIELD_BYTES = 255;
    private const POLICY_BYTES = 65535;

    /**
     * @param ViolationInterfaceFactory $violationFactory
     */
    public function __construct(private readonly ViolationInterfaceFactory $violationFactory)
    {
    }

    /**
     * Sanitized copy of the violation.
     *
     * @param ViolationInterface $violation
     * @return ViolationInterface
     */
    public function sanitize(ViolationInterface $violation): ViolationInterface
    {
        return $this->violationFactory->create([
            'documentUri' => $this->text($this->withoutQuery($violation->getDocumentUri())),
            'blockedUri' => $this->text($this->withoutQuery($violation->getBlockedUri())),
            'effectiveDirective' => $this->text($violation->getEffectiveDirective()),
            'violatedDirective' => $this->text($violation->getViolatedDirective()),
            'originalPolicy' => $this->text($violation->getOriginalPolicy(), self::POLICY_BYTES),
            'disposition' => $this->text($violation->getDisposition()),
            'referrer' => $this->text($this->withoutQuery($violation->getReferrer())),
            'scriptSample' => $this->text($violation->getScriptSample()),
            'statusCode' => $violation->getStatusCode(),
            'sourceFile' => $this->text($this->withoutQuery($violation->getSourceFile())),
            'lineNumber' => $violation->getLineNumber(),
        ]);
    }

    /**
     * URL without query string and fragment.
     *
     * @param string $url
     * @return string
     */
    private function withoutQuery(string $url): string
    {
        $end = strcspn($url, '?#');

        return substr($url, 0, $end);
    }

    /**
     * Valid UTF-8 within the byte limit, without characters a utf8 column rejects.
     *
     * @param string $value
     * @param int $bytes
     * @return string
     */
    private function text(string $value, int $bytes = self::FIELD_BYTES): string
    {
        $value = mb_scrub($value, 'UTF-8');
        $value = (string)preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\u{FFFD}", $value);

        return mb_strcut($value, 0, $bytes, 'UTF-8');
    }
}
