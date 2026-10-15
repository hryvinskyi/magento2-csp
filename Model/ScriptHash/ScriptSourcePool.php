<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

/**
 * The kinds of content the script hash command can scan, by code.
 *
 * Holding it builds none of the sources' services, so the console can list the codes before any store exists.
 */
class ScriptSourcePool
{
    /**
     * @param array<string, ScriptSourceInterface> $sources Source code => source
     */
    public function __construct(private readonly array $sources = [])
    {
    }

    /**
     * Codes of the available sources.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->sources);
    }

    /**
     * The source with the code.
     *
     * @param string $code
     * @return ScriptSourceInterface
     * @throws \InvalidArgumentException For an unknown code
     */
    public function get(string $code): ScriptSourceInterface
    {
        return $this->sources[$code] ?? throw new \InvalidArgumentException(
            sprintf('Unknown content type "%s". Use one of: %s.', $code, implode(', ', $this->codes()))
        );
    }
}
