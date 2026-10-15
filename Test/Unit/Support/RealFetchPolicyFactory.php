<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Csp\Model\Policy\FetchPolicyFactory;

/**
 * Generated-style fetch policy factory that builds real policies from the constructor arguments it receives.
 */
class RealFetchPolicyFactory extends FetchPolicyFactory
{
    /**
     * No object manager is needed.
     */
    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return FetchPolicy
     */
    public function create(array $data = [])
    {
        $id = $data['id'] ?? '';
        $hosts = $data['hostSources'] ?? [];
        $schemes = $data['schemeSources'] ?? [];
        $hashes = $data['hashValues'] ?? [];

        return new FetchPolicy(
            is_string($id) ? $id : '',
            (bool)($data['noneAllowed'] ?? true),
            is_array($hosts) ? array_values(array_filter($hosts, 'is_string')) : [],
            is_array($schemes) ? array_values(array_filter($schemes, 'is_string')) : [],
            (bool)($data['selfAllowed'] ?? false),
            false,
            false,
            [],
            is_array($hashes) ? array_filter($hashes, 'is_string') : []
        );
    }
}
