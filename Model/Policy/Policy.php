<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;

/**
 * Immutable Content-Security-Policy header value.
 */
class Policy implements PolicyInterface
{
    /**
     * Header value, built once: the splitter and the header processor ask for it and its length repeatedly.
     */
    private ?string $headerValue = null;

    /**
     * @param array<string, list<string>> $directives Lower-case directive name => source tokens, in header order
     */
    public function __construct(private array $directives = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function names(): array
    {
        return array_keys($this->directives);
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->directives);
    }

    /**
     * @inheritDoc
     */
    public function sources(string $name): array
    {
        return $this->directives[$name] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function directives(): array
    {
        return $this->directives;
    }

    /**
     * @inheritDoc
     */
    public function withSources(string $name, array $sources): PolicyInterface
    {
        $copy = clone $this;
        $copy->directives[$name] = $sources;
        $copy->headerValue = null;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function without(string $name): PolicyInterface
    {
        $copy = clone $this;
        unset($copy->directives[$name]);
        $copy->headerValue = null;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function toHeaderValue(): string
    {
        if ($this->headerValue !== null) {
            return $this->headerValue;
        }
        $parts = [];
        foreach ($this->directives as $name => $sources) {
            $parts[] = $sources === [] ? $name : $name . ' ' . implode(' ', $sources);
        }

        return $this->headerValue = implode('; ', $parts);
    }

    /**
     * @inheritDoc
     */
    public function byteLength(): int
    {
        return strlen($this->toHeaderValue());
    }
}
