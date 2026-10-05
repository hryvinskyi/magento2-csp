<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\Type\Collection as CollectionCache;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enabled whitelist entries that apply to a store and an area, cached in the collection cache.
 *
 * An entry whose directive or value breaks the whitelist rules (saved before they were enforced) is left out and
 * logged when the cache is filled.
 */
class ActiveEntries
{
    public const CACHE_TAG = 'HRYVINSKYI_CSP_WHITELIST';
    private const CACHE_KEY = 'hryvinskyi_csp_whitelist_%d_%s';
    private const CACHE_LIFETIME = 86400;

    /**
     * @var array<string, list<array{policy: string, value_type: string, value_algorithm: string, value: string}>>
     */
    private array $loaded = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param CollectionCache $cache
     * @param StateInterface $cacheState
     * @param SerializerInterface $serializer
     * @param WhitelistableDirectives $directives
     * @param SourceValueRules $sourceValueRules
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionCache $cache,
        private readonly StateInterface $cacheState,
        private readonly SerializerInterface $serializer,
        private readonly WhitelistableDirectives $directives,
        private readonly SourceValueRules $sourceValueRules,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Entries that apply to the store and the area.
     *
     * @param int $storeId
     * @param Area $area
     * @return list<array{policy: string, value_type: string, value_algorithm: string, value: string}>
     */
    public function forScope(int $storeId, Area $area): array
    {
        $key = sprintf(self::CACHE_KEY, $storeId, $area->value);
        if (isset($this->loaded[$key])) {
            return $this->loaded[$key];
        }
        $cacheEnabled = $this->cacheState->isEnabled(CollectionCache::TYPE_IDENTIFIER);
        $cached = $cacheEnabled ? $this->cache->load($key) : false;
        $entries = is_string($cached) ? $this->decode($cached) : null;
        if ($entries === null) {
            $entries = $this->query($storeId, $area);
            $serialized = $this->serializer->serialize($entries);
            if ($cacheEnabled && is_string($serialized)) {
                $this->cache->save($serialized, $key, [self::CACHE_TAG], self::CACHE_LIFETIME);
            }
        }

        return $this->loaded[$key] = $entries;
    }

    /**
     * Entries from the database, those breaking the whitelist rules left out.
     *
     * @param int $storeId
     * @param Area $area
     * @return list<array{policy: string, value_type: string, value_algorithm: string, value: string}>
     */
    private function query(int $storeId, Area $area): array
    {
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($storeId)
            ->addAreaFilter($area);
        $entries = [];
        $skipped = [];
        foreach ($collection->getItems() as $item) {
            $entry = [
                'policy' => (string)$item->getPolicy(),
                'value_type' => (string)$item->getValueType(),
                'value_algorithm' => (string)$item->getValueAlgorithm(),
                'value' => (string)$item->getValue(),
            ];
            if ($this->isRenderable($entry)) {
                $entries[] = $entry;
            } else {
                $skipped[] = (int)$item->getRuleId();
            }
        }
        if ($skipped !== []) {
            $this->logger->warning(
                'CSP whitelist entries left out of the policy because their directive or value is not allowed.',
                ['rule_ids' => $skipped]
            );
        }

        return $entries;
    }

    /**
     * Whether the entry may reach a header.
     *
     * @param array{policy: string, value_type: string, value_algorithm: string, value: string} $entry
     * @return bool
     */
    private function isRenderable(array $entry): bool
    {
        if (!$this->directives->isWhitelistable($entry['policy'])) {
            return false;
        }

        return match (ValueType::tryFrom($entry['value_type'])) {
            ValueType::HOST => $this->sourceValueRules->hostError($entry['value']) === null,
            ValueType::HASH => $this->sourceValueRules->hashError($entry['value_algorithm'], $entry['value']) === null,
            null => false,
        };
    }

    /**
     * Entries from a cache record, or null when the record is not a list of entries.
     *
     * @param string $cached
     * @return list<array{policy: string, value_type: string, value_algorithm: string, value: string}>|null
     */
    private function decode(string $cached): ?array
    {
        try {
            $rows = $this->serializer->unserialize($cached);
        } catch (\InvalidArgumentException) {
            return null;
        }
        if (!is_array($rows)) {
            return null;
        }
        $entries = [];
        foreach ($rows as $row) {
            if (!is_array($row)
                || !is_string($row['policy'] ?? null)
                || !is_string($row['value_type'] ?? null)
                || !is_string($row['value_algorithm'] ?? null)
                || !is_string($row['value'] ?? null)
            ) {
                return null;
            }
            $entries[] = [
                'policy' => $row['policy'],
                'value_type' => $row['value_type'],
                'value_algorithm' => $row['value_algorithm'],
                'value' => $row['value'],
            ];
        }

        return $entries;
    }
}
