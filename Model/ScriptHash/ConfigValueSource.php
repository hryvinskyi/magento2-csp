<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Configuration values holding a `<script` that apply to a store view: default, its website's and its own.
 */
class ConfigValueSource implements ScriptSourceInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function contents(int $storeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('core_config_data'), ['path', 'scope', 'scope_id', 'value'])
            ->where('value LIKE ?', '%<script%')
            ->where(implode(' OR ', [
                $connection->quoteInto('scope = ?', 'default'),
                '(' . $connection->quoteInto('scope IN (?)', [ScopeInterface::SCOPE_WEBSITE, ScopeInterface::SCOPE_WEBSITES])
                    . $connection->quoteInto(' AND scope_id = ?', $websiteId) . ')',
                '(' . $connection->quoteInto('scope IN (?)', [ScopeInterface::SCOPE_STORE, ScopeInterface::SCOPE_STORES])
                    . $connection->quoteInto(' AND scope_id = ?', $storeId) . ')',
            ]));
        $contents = [];
        foreach ($connection->fetchAll($select) as $row) {
            if (!is_array($row) || !is_string($row['value'] ?? null)) {
                continue;
            }
            $contents[] = [
                'label' => sprintf(
                    'Configuration "%s" (%s %s)',
                    is_scalar($row['path'] ?? null) ? (string)$row['path'] : '',
                    is_scalar($row['scope'] ?? null) ? (string)$row['scope'] : '',
                    is_scalar($row['scope_id'] ?? null) ? (string)$row['scope_id'] : ''
                ),
                'html' => $row['value'],
                'warning' => null,
            ];
        }

        return $contents;
    }
}
