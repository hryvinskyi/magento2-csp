<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Typed access to the integration object manager and the database.
 */
trait ResolvesServices
{
    /**
     * Shared instance of a type.
     *
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function service(string $type): object
    {
        $service = Bootstrap::getObjectManager()->get($type);
        if (!$service instanceof $type) {
            throw new \LogicException(sprintf('The object manager returned no %s.', $type));
        }

        return $service;
    }

    /**
     * New instance of a type.
     *
     * @template T of object
     * @param class-string<T> $type
     * @param array<string, mixed> $arguments
     * @return T
     */
    private function newInstance(string $type, array $arguments = []): object
    {
        $instance = Bootstrap::getObjectManager()->create($type, $arguments);
        if (!$instance instanceof $type) {
            throw new \LogicException(sprintf('The object manager created no %s.', $type));
        }

        return $instance;
    }

    /**
     * @return AdapterInterface
     */
    private function connection(): AdapterInterface
    {
        return $this->service(ResourceConnection::class)->getConnection();
    }

    /**
     * @param string $name
     * @return string
     */
    private function table(string $name): string
    {
        return $this->service(ResourceConnection::class)->getTableName($name);
    }

    /**
     * Last auto-increment id the connection generated.
     *
     * @return int
     */
    private function lastInsertId(): int
    {
        $id = $this->connection()->fetchOne('SELECT LAST_INSERT_ID()');

        return is_numeric($id) ? (int)$id : 0;
    }

    /**
     * Rows of a select with every value as a string, null kept.
     *
     * @param \Magento\Framework\DB\Select $select
     * @return list<array<string, string|null>>
     */
    private function rows(\Magento\Framework\DB\Select $select): array
    {
        $rows = [];
        foreach ($this->connection()->fetchAll($select) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = array_map(
                static fn (mixed $value): ?string => is_scalar($value) ? (string)$value : null,
                array_filter($row, 'is_string', ARRAY_FILTER_USE_KEY)
            );
        }

        return $rows;
    }
}
