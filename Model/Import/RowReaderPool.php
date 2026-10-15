<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

/**
 * Picks the row reader for an import file's extension.
 */
class RowReaderPool
{
    /**
     * @param array<string, WhitelistRowReaderInterface> $readers
     */
    public function __construct(private readonly array $readers = [])
    {
    }

    /**
     * Reader of the extension, or null when none reads it.
     *
     * @param string $extension
     * @return WhitelistRowReaderInterface|null
     */
    public function forExtension(string $extension): ?WhitelistRowReaderInterface
    {
        $extension = strtolower($extension);
        foreach ($this->readers as $reader) {
            if ($reader->supports($extension)) {
                return $reader;
            }
        }

        return null;
    }
}
