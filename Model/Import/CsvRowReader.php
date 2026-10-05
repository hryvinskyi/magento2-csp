<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

use Magento\Framework\File\Csv;

/**
 * Reads CSV files whose first row names the fields, such as the whitelist grid export.
 */
class CsvRowReader implements WhitelistRowReaderInterface
{
    /**
     * @param Csv $csv
     */
    public function __construct(private readonly Csv $csv)
    {
    }

    /**
     * @inheritDoc
     */
    public function supports(string $extension): bool
    {
        return $extension === 'csv';
    }

    /**
     * @inheritDoc
     */
    public function read(string $path): array
    {
        try {
            $lines = $this->csv->getData($path);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException('The CSV file cannot be read.', 0, $exception);
        }
        $header = array_shift($lines);
        if (!is_array($header) || $header === []) {
            throw new \InvalidArgumentException('The CSV file has no header row.');
        }
        $fields = array_map(static fn (mixed $name): string => is_scalar($name) ? trim((string)$name) : '', $header);

        $rows = [];
        foreach ($lines as $line) {
            if (!is_array($line) || count($line) !== count($fields)) {
                continue;
            }
            $rows[] = array_combine(
                $fields,
                array_map(static fn (mixed $value): string => is_scalar($value) ? (string)$value : '', $line)
            );
        }

        return $rows;
    }
}
