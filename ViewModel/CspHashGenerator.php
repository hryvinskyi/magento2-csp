<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class CspHashGenerator implements ArgumentInterface
{
    public function __construct(private readonly \Hryvinskyi\Csp\Api\CspHashGeneratorInterface $cspHashGenerator)
    {
    }

    /**
     * Generate sha256 hash for the given script
     *
     * @param string $script
     * @return string
     */
    public function getHash(string $script): string
    {
        return $this->cspHashGenerator->execute($script);
    }
}