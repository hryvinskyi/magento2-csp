<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * One CSP violation a browser reported, whatever report format carried it.
 *
 * @api
 */
interface ViolationInterface
{
    /**
     * URL of the page the violation happened on.
     *
     * @return string
     */
    public function getDocumentUri(): string;

    /**
     * What was blocked: a URL, or `inline`, `eval`, `wasm-eval`, `data`, `blob`…
     *
     * @return string
     */
    public function getBlockedUri(): string;

    /**
     * Directive the browser enforced, e.g. `script-src-elem`.
     *
     * @return string
     */
    public function getEffectiveDirective(): string;

    /**
     * Directive as the browser named it in the legacy format; the effective directive otherwise.
     *
     * @return string
     */
    public function getViolatedDirective(): string;

    /**
     * The policy the page was served with.
     *
     * @return string
     */
    public function getOriginalPolicy(): string;

    /**
     * `enforce` or `report`.
     *
     * @return string
     */
    public function getDisposition(): string;

    /**
     * Referrer of the page.
     *
     * @return string
     */
    public function getReferrer(): string;

    /**
     * Start of the blocked inline code, when the policy asks for samples.
     *
     * @return string
     */
    public function getScriptSample(): string;

    /**
     * HTTP status of the page.
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Script that caused the violation.
     *
     * @return string
     */
    public function getSourceFile(): string;

    /**
     * Line in the source file.
     *
     * @return int|null
     */
    public function getLineNumber(): ?int;
}
