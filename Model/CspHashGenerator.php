<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;

/**
 * Computes the CSP hash of inline content the way a browser does.
 *
 * The HTML parser turns CRLF and CR into LF before the browser hashes the element's text, so line endings are
 * normalised the same way; the bytes are hashed as they are, without any encoding conversion.
 */
class CspHashGenerator implements CspHashGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(string $script): string
    {
        return base64_encode(hash('sha256', str_replace(["\r\n", "\r"], "\n", $script), true));
    }
}
