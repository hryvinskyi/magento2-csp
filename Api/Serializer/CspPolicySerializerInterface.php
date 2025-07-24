<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Serializer;

use Magento\Csp\Api\Data\PolicyInterface;

interface CspPolicySerializerInterface
{
    /**
     * Serialize CSP policies to array format for caching
     *
     * @param PolicyInterface[] $policies
     * @return array<string, array<string, mixed>>
     */
    public function serialize(array $policies): array;

    /**
     * Unserialize CSP policies from cached array format
     *
     * @param array<string, array<string, mixed>> $data
     * @return PolicyInterface[]
     */
    public function unserialize(array $data): array;
}