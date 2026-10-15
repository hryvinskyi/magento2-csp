<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;

/**
 * Immutable reported violation.
 */
class Violation implements ViolationInterface
{
    /**
     * @param string $documentUri
     * @param string $blockedUri
     * @param string $effectiveDirective
     * @param string $violatedDirective
     * @param string $originalPolicy
     * @param string $disposition
     * @param string $referrer
     * @param string $scriptSample
     * @param int $statusCode
     * @param string $sourceFile
     * @param int|null $lineNumber
     */
    public function __construct(
        private readonly string $documentUri,
        private readonly string $blockedUri,
        private readonly string $effectiveDirective,
        private readonly string $violatedDirective = '',
        private readonly string $originalPolicy = '',
        private readonly string $disposition = '',
        private readonly string $referrer = '',
        private readonly string $scriptSample = '',
        private readonly int $statusCode = 0,
        private readonly string $sourceFile = '',
        private readonly ?int $lineNumber = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getDocumentUri(): string
    {
        return $this->documentUri;
    }

    /**
     * @inheritDoc
     */
    public function getBlockedUri(): string
    {
        return $this->blockedUri;
    }

    /**
     * @inheritDoc
     */
    public function getEffectiveDirective(): string
    {
        return $this->effectiveDirective;
    }

    /**
     * @inheritDoc
     */
    public function getViolatedDirective(): string
    {
        return $this->violatedDirective !== '' ? $this->violatedDirective : $this->effectiveDirective;
    }

    /**
     * @inheritDoc
     */
    public function getOriginalPolicy(): string
    {
        return $this->originalPolicy;
    }

    /**
     * @inheritDoc
     */
    public function getDisposition(): string
    {
        return $this->disposition;
    }

    /**
     * @inheritDoc
     */
    public function getReferrer(): string
    {
        return $this->referrer;
    }

    /**
     * @inheritDoc
     */
    public function getScriptSample(): string
    {
        return $this->scriptSample;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * @inheritDoc
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }
}
