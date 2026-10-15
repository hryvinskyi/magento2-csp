<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\Data\WhitelistSearchResultsInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist as WhitelistResource;
use Hryvinskyi\Csp\Model\Whitelist\Command\GetListInterface;
use Hryvinskyi\Csp\Model\Whitelist\EntryNormalizer;
use Hryvinskyi\Csp\Model\Whitelist\EntryValidator;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistCacheInvalidator;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;

/**
 * @inheritDoc
 */
class WhitelistRepository implements WhitelistRepositoryInterface
{
    /**
     * @param WhitelistResource $resource
     * @param WhitelistInterfaceFactory $whitelistFactory
     * @param GetListInterface $getList
     * @param EntryNormalizer $normalizer
     * @param EntryValidator $validator
     * @param WhitelistCacheInvalidator $cacheInvalidator
     */
    public function __construct(
        private readonly WhitelistResource $resource,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly GetListInterface $getList,
        private readonly EntryNormalizer $normalizer,
        private readonly EntryValidator $validator,
        private readonly WhitelistCacheInvalidator $cacheInvalidator
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(WhitelistInterface $whitelist): WhitelistInterface
    {
        $model = $this->model($whitelist);
        $this->normalizer->normalize($whitelist);
        $errors = $this->validator->validate($whitelist);
        if ($errors !== []) {
            throw new CouldNotSaveException(
                __('%1', implode(' ', array_map(static fn (Phrase $error): string => (string)$error, $errors)))
            );
        }
        $existingId = $this->resource->findIdByNaturalKey(
            (string)$whitelist->getPolicy(),
            (string)$whitelist->getValueType(),
            (string)$whitelist->getValueAlgorithm(),
            (string)$whitelist->getValue(),
            $whitelist->getArea()
        );
        if ($existingId !== null && $existingId !== $whitelist->getRuleId()) {
            throw new CouldNotSaveException(__(
                'Entry %1 already allows %2 for %3 in this area.',
                $existingId,
                (string)$whitelist->getValue(),
                (string)$whitelist->getPolicy()
            ));
        }

        try {
            $this->resource->save($model);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('The whitelist entry could not be saved: %1', $exception->getMessage()), $exception);
        }
        $this->cacheInvalidator->invalidate();

        return $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $whitelistId): WhitelistInterface
    {
        $whitelist = $this->findById($whitelistId);
        if ($whitelist === null) {
            throw new NoSuchEntityException(__('Whitelist entry %1 does not exist.', $whitelistId));
        }

        return $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function findById(int $whitelistId): ?WhitelistInterface
    {
        $whitelist = $this->whitelistFactory->create();
        $this->resource->load($this->model($whitelist), $whitelistId);

        return $whitelist->getRuleId() === null ? null : $whitelist;
    }

    /**
     * @inheritdoc
     */
    public function findByNaturalKey(
        string $policy,
        string $valueType,
        string $valueAlgorithm,
        string $value,
        string $area
    ): ?WhitelistInterface {
        $id = $this->resource->findIdByNaturalKey($policy, $valueType, $valueAlgorithm, $value, $area);

        return $id === null ? null : $this->findById($id);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): WhitelistSearchResultsInterface
    {
        return $this->getList->execute($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function delete(WhitelistInterface $whitelist): bool
    {
        try {
            $this->resource->delete($this->model($whitelist));
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('The whitelist entry could not be deleted: %1', $exception->getMessage()),
                $exception
            );
        }
        $this->cacheInvalidator->invalidate();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $whitelistId): bool
    {
        return $this->delete($this->getById($whitelistId));
    }

    /**
     * The entry as the model the resource model persists.
     *
     * @param WhitelistInterface $whitelist
     * @return AbstractModel
     * @throws \InvalidArgumentException
     */
    private function model(WhitelistInterface $whitelist): AbstractModel
    {
        if (!$whitelist instanceof AbstractModel) {
            throw new \InvalidArgumentException(sprintf('%s cannot be persisted by this repository.', $whitelist::class));
        }

        return $whitelist;
    }
}
