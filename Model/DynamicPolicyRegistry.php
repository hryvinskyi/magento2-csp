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
use Psr\Log\LoggerInterface;

/**
 * Adds page allowances to Magento's dynamic policy collector.
 *
 * Allowances come from templates while the page renders, so a source that cannot be sent safely is logged and
 * skipped instead of failing the block: the page keeps its HTML and the browser blocks what was not allowed.
 */
class DynamicPolicyRegistry implements DynamicPolicyRegistryInterface
{
    private const DEFAULT_ALGORITHM = 'sha256';

    /**
     * Base64 form of a digest of each hash algorithm CSP accepts.
     */
    private const DIGEST_PATTERNS = [
        'sha256' => '~^[A-Za-z0-9+/]{43}=$~',
        'sha384' => '~^[A-Za-z0-9+/]{64}$~',
        'sha512' => '~^[A-Za-z0-9+/]{86}==$~',
    ];

    /**
     * @param DynamicCollector $dynamicCollector
     * @param FetchPolicyFactory $fetchPolicyFactory
     * @param HostSourceParser $hostSourceParser
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly DynamicCollector $dynamicCollector,
        private readonly FetchPolicyFactory $fetchPolicyFactory,
        private readonly HostSourceParser $hostSourceParser,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function allow(string $directive, array $hosts = [], array $hashes = [], bool $self = false): void
    {
        if (!in_array($directive, FetchPolicy::POLICIES, true)) {
            $this->logger->warning(sprintf('CSP allowance skipped: "%s" is not a fetch directive.', $directive));

            return;
        }

        $schemes = [];
        $hostSources = [];
        foreach ($hosts as $host) {
            if (SourceKind::of($host) === SourceKind::Scheme) {
                $schemes[] = rtrim($host, ':');
                continue;
            }
            if ($this->hostSourceParser->isHostSource($host)) {
                $hostSources[] = $host;
                continue;
            }
            $this->logger->warning(
                sprintf('CSP %s source skipped: "%s" is not a host or scheme source.', $directive, $host)
            );
        }
        $hashValues = $this->hashValues($directive, $hashes);

        if ($schemes === [] && $hostSources === [] && $hashValues === [] && !$self) {
            return;
        }

        $this->dynamicCollector->add($this->fetchPolicyFactory->create([
            'id' => $directive,
            'noneAllowed' => false,
            'hostSources' => $hostSources,
            'schemeSources' => $schemes,
            'selfAllowed' => $self,
            'hashValues' => $hashValues,
        ]));
    }

    /**
     * Valid digests as Magento's fetch policy takes them: digest => algorithm.
     *
     * A string key is a digest and its value the algorithm (the shape Magento renders, used by 1.x callers); an
     * integer key means the value is a sha256 digest.
     *
     * @param string $directive
     * @param array<int|string, string> $hashes
     * @return array<string, string>
     */
    private function hashValues(string $directive, array $hashes): array
    {
        $values = [];
        foreach ($hashes as $key => $value) {
            $digest = is_string($key) ? $key : $value;
            $algorithm = is_string($key) ? strtolower($value) : self::DEFAULT_ALGORITHM;
            $pattern = self::DIGEST_PATTERNS[$algorithm] ?? null;
            if ($pattern === null || preg_match($pattern, $digest) !== 1) {
                $this->logger->warning(sprintf(
                    'CSP %s hash skipped: "%s" is not a base64 %s digest.',
                    $directive,
                    $digest,
                    $algorithm
                ));
                continue;
            }
            $values[$digest] = $algorithm;
        }

        return $values;
    }
}
