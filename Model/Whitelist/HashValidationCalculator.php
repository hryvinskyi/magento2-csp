<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;
use Hryvinskyi\Csp\Api\HashValidationCalculatorInterface;
use Hryvinskyi\Csp\Model\Config\Source\HashValidationOptions;

/**
 * Calculates hash validation status for CSP whitelist entries
 */
class HashValidationCalculator implements HashValidationCalculatorInterface
{
    /**
     * @param CspHashGeneratorInterface $hashGenerator
     */
    public function __construct(
        private readonly CspHashGeneratorInterface $hashGenerator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function calculateForItems(array $items): array
    {
        foreach ($items as $key => $item) {
            $items[$key]['hash_validation'] = $this->calculateItemStatus($item);
        }

        return $items;
    }

    /**
     * Calculate hash validation status for a single item
     *
     * @param array<string, mixed> $item
     * @return int
     */
    private function calculateItemStatus(array $item): int
    {
        $valueType = $item['value_type'] ?? '';
        $algorithm = $item['value_algorithm'] ?? '';

        if ($valueType !== 'hash' || $algorithm !== 'sha256') {
            return HashValidationOptions::NOT_APPLICABLE;
        }

        $scriptContent = $item['script_content'] ?? '';
        $storedHash = $item['value'] ?? '';

        if (!is_string($scriptContent) || !is_string($storedHash) || $scriptContent === '' || $storedHash === '') {
            return HashValidationOptions::NOT_VERIFIED;
        }

        $calculatedHash = $this->hashGenerator->execute($scriptContent);

        return $calculatedHash === $storedHash
            ? HashValidationOptions::VALID
            : HashValidationOptions::INVALID;
    }
}
