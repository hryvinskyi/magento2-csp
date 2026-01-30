<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;

use Hryvinskyi\Csp\Api\BlockedUriValueExtractorInterface;
use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Exception\LocalizedException;

class CspReportConverter implements CspReportConverterInterface
{
    private const POLICY_MAPPING = [
        'script-src-elem' => 'script-src',
        'style-src-elem' => 'style-src',
    ];

    private const INLINE_URI_VALUES = ['inline', 'unsafe-inline'];

    public function __construct(
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly BlockedUriValueExtractorInterface $blockedUriValueExtractor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function convert(ReportInterface $cspReport): WhitelistInterface
    {
        $blockedUri = $cspReport->getBlockedUri();
        $identifier = $this->generateIdentifier(
            $blockedUri,
            $cspReport->getSourceFile(),
            $cspReport->getLineNumber()
        );
        $policy = $this->normalizePolicy($cspReport->getEffectiveDirective());
        [$value, $valueType] = $this->blockedUriValueExtractor->extractValueAndType($blockedUri);

        $this->validatePolicy($policy);

        return $this->whitelistFactory->create()
            ->setIdentifier(md5($identifier))
            ->setPolicy($policy)
            ->setValueType($valueType)
            ->setValue($value)
            ->setStoreIds('0')
            ->setStatus(1);
    }

    /**
     * @inheritDoc
     */
    public function convertFromArray(array $cspReport): WhitelistInterface
    {
        $blockedUri = $cspReport['blocked-uri'] ?? '';
        $identifier = $this->generateIdentifier(
            $blockedUri,
            $cspReport['source-file'] ?? '',
            $cspReport['line-number'] ?? null
        );
        $policy = $this->normalizePolicy($cspReport['effective-directive'] ?? '');
        [$value, $valueType] = $this->blockedUriValueExtractor->extractValueAndType($blockedUri);

        $this->validatePolicy($policy);

        return $this->whitelistFactory->create()
            ->setIdentifier(md5($identifier))
            ->setPolicy($policy)
            ->setValueType($valueType)
            ->setValue($value)
            ->setStoreIds('0')
            ->setStatus(1);
    }

    /**
     * @inheritDoc
     */
    public function normalizePolicy(string $policy): string
    {
        return self::POLICY_MAPPING[$policy] ?? $policy;
    }

    /**
     * Generate identifier for the whitelist entry.
     *
     * @param string $blockedUri
     * @param string|null $sourceFile
     * @param string|int|null $lineNumber
     * @return string
     */
    private function generateIdentifier(string $blockedUri, ?string $sourceFile, string|int|null $lineNumber): string
    {
        if (in_array($blockedUri, self::INLINE_URI_VALUES, true)) {
            return sprintf('%s:%s', $sourceFile ?? '', $lineNumber ?? 'unknown');
        }

        return $blockedUri;
    }

    /**
     * Validate that the policy is a supported fetch policy.
     *
     * @param string $policy
     * @return void
     * @throws LocalizedException
     */
    private function validatePolicy(string $policy): void
    {
        if (!in_array($policy, FetchPolicy::POLICIES, true)) {
            throw new LocalizedException(
                __('Cannot convert CSP report: unsupported policy detected (%1).', $policy)
            );
        }
    }
}
