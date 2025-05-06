<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */


declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;

class CspHashGenerator implements CspHashGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(string $script): string
    {
        // Convert to UTF-8 and LF line endings
        $script = mb_convert_encoding($script, 'UTF-8', 'auto');
        $script = preg_replace('/\r\n|\r|\n/', "\n", $script);

        return base64_encode(hash('sha256', $script, true));
    }
}