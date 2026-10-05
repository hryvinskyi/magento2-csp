<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

/**
 * One thing a page asks the browser to allow, for the test oracle.
 */
class CspRequest
{
    public const KIND_URL = 'url';
    public const KIND_INLINE = 'inline';
    public const KIND_ATTRIBUTE = 'attribute';
    public const KIND_EVAL = 'eval';
    public const KIND_WASM = 'wasm';

    /**
     * @param string $type script, style, worker, frame, fenced-frame, img, font, connect, media, object, manifest
     * @param string $kind One of the KIND_* constants
     * @param string $url URL of a fetched resource
     * @param string|null $nonce Nonce attribute of an inline element
     * @param string|null $hash Base64 sha256 digest of inline content
     */
    public function __construct(
        public readonly string $type,
        public readonly string $kind,
        public readonly string $url = '',
        public readonly ?string $nonce = null,
        public readonly ?string $hash = null
    ) {
    }

    /**
     * Readable name for assertion messages.
     *
     * @return string
     */
    public function label(): string
    {
        return trim(sprintf(
            '%s %s %s%s%s',
            $this->type,
            $this->kind,
            $this->url,
            $this->nonce !== null ? ' nonce=' . $this->nonce : '',
            $this->hash !== null ? ' hash=' . $this->hash : ''
        ));
    }
}
