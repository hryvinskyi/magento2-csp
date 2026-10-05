<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\DynamicPolicyRegistryInterface;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\SourceKind;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Csp\Model\Policy\FetchPolicyFactory;

/**
 * Adds page allowances to Magento's dynamic policy collector.
 */
class DynamicPolicyRegistry implements DynamicPolicyRegistryInterface
{
    private const HASH_PATTERN = '~^[A-Za-z0-9+/]{43}=$~';

    /**
     * @param DynamicCollector $dynamicCollector
     * @param FetchPolicyFactory $fetchPolicyFactory
     * @param HostSourceParser $hostSourceParser
     */
    public function __construct(
        private readonly DynamicCollector $dynamicCollector,
        private readonly FetchPolicyFactory $fetchPolicyFactory,
        private readonly HostSourceParser $hostSourceParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function allow(string $directive, array $hosts = [], array $hashes = [], bool $self = false): void
    {
        if (!in_array($directive, FetchPolicy::POLICIES, true)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a fetch directive.', $directive));
        }
        foreach ($hosts as $host) {
            if (SourceKind::of($host) !== SourceKind::Scheme && !$this->hostSourceParser->isHostSource($host)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a host or scheme source.', $host));
            }
        }
        foreach ($hashes as $hash) {
            if (preg_match(self::HASH_PATTERN, $hash) !== 1) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a base64 sha256 digest.', $hash));
            }
        }

        $schemes = array_values(array_filter($hosts, static fn (string $host): bool => SourceKind::of($host) === SourceKind::Scheme));
        $hostSources = array_values(array_diff($hosts, $schemes));

        $this->dynamicCollector->add($this->fetchPolicyFactory->create([
            'id' => $directive,
            'noneAllowed' => false,
            'hostSources' => $hostSources,
            'schemeSources' => array_map(static fn (string $scheme): string => rtrim($scheme, ':'), $schemes),
            'selfAllowed' => $self,
            'hashValues' => array_fill_keys($hashes, 'sha256'),
        ]));
    }
}
