<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Command;

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

    private const SPECIAL_URI_MAPPINGS = [
        'data' => 'data:',
        'blob' => 'blob:',
        'unsafe-inline' => 'unsafe-inline',
        'inline' => 'unsafe-inline',
    ];

    public function __construct(
        private readonly WhitelistInterfaceFactory $whitelistFactory
    ) {
    }

    public function convert(ReportInterface $cspReport): WhitelistInterface
    {
        $whitelist = $this->whitelistFactory->create();
        $blockedUri = $cspReport->getBlockedUri();
        $identifier = $this->generateIdentifier($cspReport);
        $policy = $this->normalizePolicy($cspReport->getEffectiveDirective());
        [$value, $valueType] = $this->determineValueAndType($blockedUri);

        $this->validatePolicy($policy);

        return $whitelist
            ->setIdentifier(md5($identifier))
            ->setPolicy($policy)
            ->setValueType($valueType)
            ->setValue($value)
            ->setStoreIds('0')
            ->setStatus(1);
    }

    private function generateIdentifier(ReportInterface $cspReport): string
    {
        return $cspReport->getBlockedUri() === 'inline'
            ? sprintf('%s:%s', $cspReport->getSourceFile(), $cspReport->getLineNumber() ?? 'unknown')
            : $cspReport->getBlockedUri();
    }

    private function normalizePolicy(string $policy): string
    {
        return self::POLICY_MAPPING[$policy] ?? $policy;
    }

    private function determineValueAndType(string $blockedUri): array
    {
        if (isset(self::SPECIAL_URI_MAPPINGS[$blockedUri])) {
            return [self::SPECIAL_URI_MAPPINGS[$blockedUri], 'host'];
        }

        if (filter_var($blockedUri, FILTER_VALIDATE_URL) || strpos($blockedUri, '.') !== false) {
            return [parse_url($blockedUri, PHP_URL_HOST), 'host'];
        }

        throw new LocalizedException(
            __('Cannot convert CSP report: unsupported or hash value type detected. Only host supported.')
        );
    }

    private function validatePolicy(string $policy): void
    {
        if (!in_array($policy, FetchPolicy::POLICIES, true)) {
            throw new LocalizedException(
                __('Cannot convert CSP report: unsupported policy detected.')
            );
        }
    }
}