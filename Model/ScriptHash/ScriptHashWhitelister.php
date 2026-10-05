<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\HashAlgorithm;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Allows an inline storefront script by its sha256 hash in a store view.
 *
 * An entry for the same hash gains the store instead of being duplicated.
 */
class ScriptHashWhitelister
{
    public const CREATED = 'created';
    public const EXTENDED = 'extended';
    public const EXISTS = 'exists';
    private const POLICY = 'script-src';

    /**
     * @param CspHashGeneratorInterface $hashGenerator
     * @param WhitelistRepositoryInterface $whitelistRepository
     * @param WhitelistInterfaceFactory $whitelistFactory
     */
    public function __construct(
        private readonly CspHashGeneratorInterface $hashGenerator,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly WhitelistInterfaceFactory $whitelistFactory
    ) {
    }

    /**
     * Base64 sha256 digest of the script as the browser hashes it.
     *
     * @param string $script
     * @return string
     */
    public function hash(string $script): string
    {
        return $this->hashGenerator->execute($script);
    }

    /**
     * Allow the script in the store view.
     *
     * @param string $script
     * @param int $storeId
     * @return string self::CREATED, self::EXTENDED or self::EXISTS
     * @throws LocalizedException
     */
    public function whitelist(string $script, int $storeId): string
    {
        $hash = $this->hash($script);
        $existing = $this->whitelistRepository->findByNaturalKey(
            self::POLICY,
            ValueType::HASH->value,
            HashAlgorithm::SHA256->value,
            $hash,
            Area::FRONTEND->value
        );
        if ($existing !== null) {
            $stores = $existing->getStoreIds();
            if (in_array($storeId, $stores, true) || in_array(0, $stores, true)) {
                return self::EXISTS;
            }
            $existing->setStoreIds([...$stores, $storeId]);
            $this->whitelistRepository->save($existing);

            return self::EXTENDED;
        }

        $entry = $this->whitelistFactory->create();
        $entry->setIdentifier(sprintf('Inline script %s', substr($hash, 0, 12)));
        $entry->setPolicy(self::POLICY);
        $entry->setValueType(ValueType::HASH->value);
        $entry->setValueAlgorithm(HashAlgorithm::SHA256->value);
        $entry->setValue($hash);
        $entry->setStoreIds([$storeId]);
        $entry->setArea(Area::FRONTEND->value);
        $entry->setStatus(1);
        $entry->setScriptContent($script);
        $this->whitelistRepository->save($entry);

        return self::CREATED;
    }
}
