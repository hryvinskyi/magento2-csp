<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Conversion;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ValueType;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Hryvinskyi\Csp\Model\Whitelist\RedundancyRules;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Turns a report group into a whitelist allowance for the group's directive, store and area.
 *
 * An entry with the same directive, value and area gains the group's store instead of being duplicated; nothing is
 * added when an enabled entry or wildcard already allows the value there. The group and its reports are removed only
 * when the value is allowed afterwards.
 */
class ReportGroupConverter
{
    /**
     * @param UnsafeSourceRules $unsafeSourceRules
     * @param SourceValueRules $sourceValueRules
     * @param RedundancyRules $redundancyRules
     * @param CollectionFactory $whitelistCollectionFactory
     * @param WhitelistRepositoryInterface $whitelistRepository
     * @param WhitelistInterfaceFactory $whitelistFactory
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param ConversionResultFactory $resultFactory
     */
    public function __construct(
        private readonly UnsafeSourceRules $unsafeSourceRules,
        private readonly SourceValueRules $sourceValueRules,
        private readonly RedundancyRules $redundancyRules,
        private readonly CollectionFactory $whitelistCollectionFactory,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly ConversionResultFactory $resultFactory
    ) {
    }

    /**
     * Convert the group.
     *
     * @param ReportGroupInterface $group
     * @return ConversionResult
     * @throws LocalizedException When the entry or the group cannot be saved or removed
     */
    public function convert(ReportGroupInterface $group): ConversionResult
    {
        $policy = (string)$group->getPolicy();
        $value = $this->sourceValueRules->normalizeHost((string)$group->getValue());
        $refusal = $this->unsafeSourceRules->refusal($policy, $value);
        if ($refusal !== null) {
            return $this->result(ConversionOutcome::Refused, null, $refusal->message($policy, $value));
        }

        $storeId = (int)$group->getStoreId();
        $area = $group->getArea();
        $candidate = ['rule_id' => PHP_INT_MAX, 'value' => $value, 'stores' => [$storeId], 'area' => $area];
        $result = match ($this->redundancyRules->status($candidate, $this->enabledHosts($policy))) {
            RedundancyStatusOptions::DUPLICATE => $this->result(ConversionOutcome::AlreadyAllowed),
            RedundancyStatusOptions::REDUNDANT => $this->result(ConversionOutcome::CoveredByWildcard),
            default => $this->allow($policy, $value, $storeId, $area),
        };
        if ($result->outcome->isAllowed()) {
            $this->reportGroupRepository->delete($group);
        }

        return $result;
    }

    /**
     * Extend the entry with the same natural key to the store, or create one.
     *
     * @param string $policy
     * @param string $value
     * @param int $storeId
     * @param string $area
     * @return ConversionResult
     * @throws LocalizedException
     */
    private function allow(string $policy, string $value, int $storeId, string $area): ConversionResult
    {
        $existing = $this->whitelistRepository->findByNaturalKey($policy, ValueType::HOST->value, '', $value, $area);
        if ($existing !== null && $existing->getStatus() !== 1) {
            return $this->result(ConversionOutcome::ExistsDisabled, $existing->getRuleId());
        }
        if ($existing !== null) {
            $existing->setStoreIds([...$existing->getStoreIds(), $storeId]);
            $this->whitelistRepository->save($existing);

            return $this->result(ConversionOutcome::StoresExtended, $existing->getRuleId());
        }

        $entry = $this->whitelistFactory->create();
        $entry->setIdentifier(sprintf('%s %s', $policy, $value));
        $entry->setPolicy($policy);
        $entry->setValueType(ValueType::HOST->value);
        $entry->setValueAlgorithm('');
        $entry->setValue($value);
        $entry->setStoreIds([$storeId]);
        $entry->setArea($area);
        $entry->setStatus(1);
        $this->whitelistRepository->save($entry);

        return $this->result(ConversionOutcome::Created, $entry->getRuleId());
    }

    /**
     * Enabled host entries of the directive.
     *
     * @param string $policy
     * @return list<array{rule_id: int, value: string, stores: list<int>, area: string}>
     */
    private function enabledHosts(string $policy): array
    {
        $collection = $this->whitelistCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter(WhitelistInterface::POLICY, ['eq' => $policy])
            ->addFieldToFilter(WhitelistInterface::VALUE_TYPE, ['eq' => ValueType::HOST->value]);

        return array_values(array_map(
            static fn (WhitelistInterface $entry): array => [
                'rule_id' => (int)$entry->getRuleId(),
                'value' => (string)$entry->getValue(),
                'stores' => array_values($entry->getStoreIds()),
                'area' => $entry->getArea(),
            ],
            $collection->getItems()
        ));
    }

    /**
     * @param ConversionOutcome $outcome
     * @param int|null $entryId
     * @param Phrase|null $refusal
     * @return ConversionResult
     */
    private function result(ConversionOutcome $outcome, ?int $entryId = null, ?Phrase $refusal = null): ConversionResult
    {
        return $this->resultFactory->create(['outcome' => $outcome, 'entryId' => $entryId, 'refusal' => $refusal]);
    }
}
