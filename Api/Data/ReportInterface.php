<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

interface ReportInterface
{
    /**
     * Status codes for report
     */
    public const STATUS_CODE_PENDING = 0;
    public const STATUS_CODE_DENIED = 1;
    public const STATUS_CODE_SKIP = 2;

    /**#@+
     * Constants for keys of data array.
     */
    public const REPORT_ID = 'report_id';
    public const BLOCKED_URI = 'blocked_uri';
    public const DISPOSITION = 'disposition';
    public const DOCUMENT_URI = 'document_uri';
    public const EFFECTIVE_DIRECTIVE = 'effective_directive';
    public const ORIGINAL_POLICY = 'original_policy';
    public const REFERRER = 'referrer';
    public const SCRIPT_SAMPLE = 'script_sample';
    public const STATUS_CODE = 'status_code';
    public const VIOLATED_DIRECTIVE = 'violated_directive';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const SOURCE_FILE = 'source_file';
    public const LINE_NUMBER = 'line_number';
    public const COUNT = 'count';
    public const STATUS = 'status';
    /**#@-*/


    /**
     * Get ReportId value
     *
     * @return int|null
     */
    public function getReportId(): ?int;

    /**
     * Set ReportId value
     *
     * @param int $reportId
     *
     * @return $this
     */
    public function setReportId(int $reportId): ReportInterface;

    /**
     * Get BlockedUri value
     *
     * @return string|null
     */
    public function getBlockedUri(): ?string;

    /**
     * Set BlockedUri value
     *
     * @param string $blockedUri
     *
     * @return $this
     */
    public function setBlockedUri(string $blockedUri): ReportInterface;

    /**
     * Get Disposition value
     *
     * @return string|null
     */
    public function getDisposition(): ?string;

    /**
     * Set Disposition value
     *
     * @param string $disposition
     *
     * @return $this
     */
    public function setDisposition(string $disposition): ReportInterface;

    /**
     * Get DocumentUri value
     *
     * @return string|null
     */
    public function getDocumentUri(): ?string;

    /**
     * Set DocumentUri value
     *
     * @param string $documentUri
     *
     * @return $this
     */
    public function setDocumentUri(string $documentUri): ReportInterface;

    /**
     * Get EffectiveDirective value
     *
     * @return string|null
     */
    public function getEffectiveDirective(): ?string;

    /**
     * Set EffectiveDirective value
     *
     * @param string $effectiveDirective
     *
     * @return $this
     */
    public function setEffectiveDirective(string $effectiveDirective): ReportInterface;

    /**
     * Get OriginalPolicy value
     *
     * @return string|null
     */
    public function getOriginalPolicy(): ?string;

    /**
     * Set OriginalPolicy value
     *
     * @param string $originalPolicy
     *
     * @return $this
     */
    public function setOriginalPolicy(string $originalPolicy): ReportInterface;

    /**
     * Get Referrer value
     *
     * @return string|null
     */
    public function getReferrer(): ?string;

    /**
     * Set Referrer value
     *
     * @param string $referrer
     *
     * @return $this
     */
    public function setReferrer(string $referrer): ReportInterface;

    /**
     * Get ScriptSample value
     *
     * @return string|null
     */
    public function getScriptSample(): ?string;

    /**
     * Set ScriptSample value
     *
     * @param string $scriptSample
     *
     * @return $this
     */
    public function setScriptSample(string $scriptSample): ReportInterface;

    /**
     * Get StatusCode value
     *
     * @return string|null
     */
    public function getStatusCode(): ?string;

    /**
     * Set StatusCode value
     *
     * @param string $statusCode
     *
     * @return $this
     */
    public function setStatusCode(string $statusCode): ReportInterface;

    /**
     * Get ViolatedDirective value
     *
     * @return string|null
     */
    public function getViolatedDirective(): ?string;

    /**
     * Set ViolatedDirective value
     *
     * @param string $violatedDirective
     *
     * @return $this
     */
    public function setViolatedDirective(string $violatedDirective): ReportInterface;

    /**
     * Get CreatedAt value
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set CreatedAt value
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): ReportInterface;

    /**
     * Get UpdatedAt value
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set UpdatedAt value
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): ReportInterface;

    /**
     * Get SourceFile value
     *
     * @return string|null
     */
    public function getSourceFile(): ?string;

    /**
     * Set SourceFile value
     *
     * @param string $sourceFile
     *
     * @return $this
     */
    public function setSourceFile(string $sourceFile): ReportInterface;

    /**
     * Get LineNumber value
     *
     * @return int|null
     */
    public function getLineNumber(): ?int;

    /**
     * Set LineNumber value
     *
     * @param int $lineNumber
     *
     * @return $this
     */
    public function setLineNumber(int $lineNumber): ReportInterface;

    /**
     * Get Count value
     *
     * @return int|null
     */
    public function getCount(): ?int;

    /**
     * Set Count value
     *
     * @param int $count
     *
     * @return $this
     */
    public function setCount(int $count): ReportInterface;

    /**
     * Get status value
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set status value
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): ReportInterface;
}
