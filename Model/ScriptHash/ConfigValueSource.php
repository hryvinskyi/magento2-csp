<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration values holding a `<script` that a store view renders.
 *
 * Values are read through the store view's configuration, so a website or store value replaces the default one and
 * values locked in `app/etc/env.php` or `app/etc/config.php` count as the storefront sees them.
 */
class ConfigValueSource implements ScriptSourceInterface
{
    private const SCRIPT_MARKER = '<script';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function contents(int $storeId): array
    {
        $configuration = $this->scopeConfig->getValue('', ScopeInterface::SCOPE_STORE, $storeId);
        $contents = [];
        foreach ($this->scriptValues(is_array($configuration) ? $configuration : [], '') as $path => $value) {
            $contents[] = [
                'label' => sprintf('Configuration "%s"', $path),
                'html' => $value,
                'warning' => null,
            ];
        }

        return $contents;
    }

    /**
     * Values of a configuration tree that hold a script, by path.
     *
     * @param array<mixed> $tree
     * @param string $prefix Path of the tree, empty at the root
     * @return array<string, string>
     */
    private function scriptValues(array $tree, string $prefix): array
    {
        $values = [];
        foreach ($tree as $key => $node) {
            $path = $prefix === '' ? (string)$key : $prefix . '/' . $key;
            if (is_array($node)) {
                $values += $this->scriptValues($node, $path);
            } elseif (is_string($node) && stripos($node, self::SCRIPT_MARKER) !== false) {
                $values[$path] = $node;
            }
        }

        return $values;
    }
}
