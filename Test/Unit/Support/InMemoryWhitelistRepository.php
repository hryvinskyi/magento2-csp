<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Whitelist\EntryValidator;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Whitelist repository kept in memory; saving validates when given a validator and assigns ids.
 */
class InMemoryWhitelistRepository implements WhitelistRepositoryInterface
{
    /**
     * @var array<int, WhitelistInterface>
     */
    public array $entries = [];

    /**
     * @param EntryValidator|null $validator
     */
    public function __construct(private readonly ?EntryValidator $validator = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function save(WhitelistInterface $whitelist): WhitelistInterface
    {
        $errors = $this->validator?->validate($whitelist) ?? [];
        if ($errors !== []) {
            throw new CouldNotSaveException($errors[0]);
        }
        if ($whitelist->getRuleId() === null) {
            $whitelist->setRuleId(count($this->entries) + 100);
        }
        $this->entries[(int)$whitelist->getRuleId()] = $whitelist;

        return $whitelist;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $whitelistId): WhitelistInterface
    {
        return $this->entries[$whitelistId] ?? throw new NoSuchEntityException();
    }

    /**
     * @inheritDoc
     */
    public function findById(int $whitelistId): ?WhitelistInterface
    {
        return $this->entries[$whitelistId] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function findByNaturalKey(
        string $policy,
        string $valueType,
        string $valueAlgorithm,
        string $value,
        string $area
    ): ?WhitelistInterface {
        foreach ($this->entries as $entry) {
            if ([$entry->getPolicy(), $entry->getValueType(), $entry->getValueAlgorithm(), $entry->getValue(), $entry->getArea()]
                === [$policy, $valueType, $valueAlgorithm, $value, $area]
            ) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): WhitelistSearchResultsInterface
    {
        throw new \LogicException('Not used by the code under test.');
    }

    /**
     * @inheritDoc
     */
    public function delete(WhitelistInterface $whitelist): bool
    {
        unset($this->entries[(int)$whitelist->getRuleId()]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $whitelistId): bool
    {
        unset($this->entries[$whitelistId]);

        return true;
    }
}
