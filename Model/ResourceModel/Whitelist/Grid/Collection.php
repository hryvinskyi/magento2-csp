<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Grid;

use Hryvinskyi\Csp\Model\Config\Source\HashValidationOptions;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\DB\Sql\Expression;

class Collection extends SearchResult
{
    private const INVALID = HashValidationOptions::INVALID;
    private const VALID = HashValidationOptions::VALID;
    private const NOT_VERIFIED = HashValidationOptions::NOT_VERIFIED;
    private const NOT_APPLICABLE = HashValidationOptions::NOT_APPLICABLE;

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->columns([
            'hash_validation' => new Expression($this->buildHashValidationExpression())
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field !== 'hash_validation') {
            return parent::addFieldToFilter($field, $condition);
        }

        $filterValue = $condition['eq'] ?? $condition['in'] ?? null;
        $this->getSelect()->where(
            $this->buildHashValidationExpression() . ' in (?)',
            $filterValue
        );

        return $this;
    }

    /**
     * Build the hash validation SQL expression
     *
     * @return string
     */
    private function buildHashValidationExpression(): string
    {
        // Normalize script content for consistent hashing across platforms
        $normalizedContent = "REPLACE(
            REPLACE(
                CONVERT(COALESCE(script_content, '') USING utf8mb4),
            '\r\n', '\n'),
        '\r', '\n')";

        // Calculate SHA256 hash and encode to base64
        $calculatedHash = "TO_BASE64(UNHEX(SHA2($normalizedContent, 256)))";

        // Build the CASE expression
        return "
            CASE
                WHEN value_type = 'hash' AND value_algorithm = 'sha256' THEN
                    CASE
                        WHEN script_content IS NOT NULL AND script_content != '' 
                             AND value IS NOT NULL AND value != '' THEN
                            CASE
                                WHEN $calculatedHash = value THEN '" . self::VALID . "'
                                ELSE '" . self::INVALID . "'
                            END
                        ELSE '" . self::NOT_VERIFIED . "'
                    END
                ELSE '" . self::NOT_APPLICABLE . "'
            END
        ";
    }
}