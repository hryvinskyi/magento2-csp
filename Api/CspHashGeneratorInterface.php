<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */


declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

interface CspHashGeneratorInterface
{
    /**
     * Generate sha256 hash for the given script
     *
     * @param string $script
     * @return string
     */
    public function execute(string $script): string;
}