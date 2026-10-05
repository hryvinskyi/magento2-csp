<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

/**
 * Reads whitelist rows from an import file of one format.
 */
interface WhitelistRowReaderInterface
{
    /**
     * Whether the reader reads files with the extension (lower-case, without the dot).
     *
     * @param string $extension
     * @return bool
     */
    public function supports(string $extension): bool;

    /**
     * Rows of the file as field name => text.
     *
     * @param string $path
     * @return list<array<string, string>>
     * @throws \InvalidArgumentException When the file is not a whitelist export of this format
     */
    public function read(string $path): array;
}
