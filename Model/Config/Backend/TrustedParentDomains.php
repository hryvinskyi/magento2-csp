<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

/**
 * Refuses trusted parent domains that are not domain names of at least two labels, such as `com` or `*.co`.
 */
class TrustedParentDomains extends Value
{
    private const DOMAIN = '~^(?:\*\.)?[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$~';

    /**
     * Validate every listed domain.
     *
     * @return $this
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $domains = preg_split('/[\s,]+/', strtolower(is_scalar($value) ? (string)$value : ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $invalid = array_filter($domains, static fn (string $domain): bool => preg_match(self::DOMAIN, $domain) !== 1);
        if ($invalid !== []) {
            throw new ValidatorException(__(
                'These are not domain names of at least two labels: %1.',
                implode(', ', $invalid)
            ));
        }
        $this->setValue(implode("\n", array_unique($domains)));

        return parent::beforeSave();
    }
}
