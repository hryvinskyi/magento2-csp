<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Magento\Framework\Model\AbstractModel;

/**
 * @method ReportResource getResource()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\Report\Collection getCollection()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\Report\Collection getResourceCollection()
 */
class Report extends AbstractModel implements ReportInterface
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_model_report';

    /**
     * @inheritdoc
     * @noinspection MagicMethodsValidityInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function _construct()
    {
        $this->_init(ReportResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getReportId(): ?int
    {
        return $this->_getData(self::REPORT_ID) === null ? null :
            (int)$this->_getData(self::REPORT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReportId(int $reportId): ReportInterface
    {
        $this->setData(self::REPORT_ID, $reportId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBlockedUri(): ?string
    {
        return $this->_getData(self::BLOCKED_URI) === null ? null :
            (string)$this->_getData(self::BLOCKED_URI);
    }

    /**
     * @inheritdoc
     */
    public function setBlockedUri(string $blockedUri): ReportInterface
    {
        $this->setData(self::BLOCKED_URI, $blockedUri);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDisposition(): ?string
    {
        return $this->_getData(self::DISPOSITION) === null ? null :
            (string)$this->_getData(self::DISPOSITION);
    }

    /**
     * @inheritdoc
     */
    public function setDisposition(string $disposition): ReportInterface
    {
        $this->setData(self::DISPOSITION, $disposition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDocumentUri(): ?string
    {
        return $this->_getData(self::DOCUMENT_URI) === null ? null :
            (string)$this->_getData(self::DOCUMENT_URI);
    }

    /**
     * @inheritdoc
     */
    public function setDocumentUri(string $documentUri): ReportInterface
    {
        $this->setData(self::DOCUMENT_URI, $documentUri);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEffectiveDirective(): ?string
    {
        return $this->_getData(self::EFFECTIVE_DIRECTIVE) === null ? null :
            (string)$this->_getData(self::EFFECTIVE_DIRECTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setEffectiveDirective(string $effectiveDirective): ReportInterface
    {
        $this->setData(self::EFFECTIVE_DIRECTIVE, $effectiveDirective);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOriginalPolicy(): ?string
    {
        return $this->_getData(self::ORIGINAL_POLICY) === null ? null :
            (string)$this->_getData(self::ORIGINAL_POLICY);
    }

    /**
     * @inheritdoc
     */
    public function setOriginalPolicy(string $originalPolicy): ReportInterface
    {
        $this->setData(self::ORIGINAL_POLICY, $originalPolicy);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReferrer(): ?string
    {
        return $this->_getData(self::REFERRER) === null ? null :
            (string)$this->_getData(self::REFERRER);
    }

    /**
     * @inheritdoc
     */
    public function setReferrer(string $referrer): ReportInterface
    {
        $this->setData(self::REFERRER, $referrer);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getScriptSample(): ?string
    {
        return $this->_getData(self::SCRIPT_SAMPLE) === null ? null :
            (string)$this->_getData(self::SCRIPT_SAMPLE);
    }

    /**
     * @inheritdoc
     */
    public function setScriptSample(string $scriptSample): ReportInterface
    {
        $this->setData(self::SCRIPT_SAMPLE, $scriptSample);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): ?string
    {
        return $this->_getData(self::STATUS_CODE) === null ? null :
            (string)$this->_getData(self::STATUS_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setStatusCode(string $statusCode): ReportInterface
    {
        $this->setData(self::STATUS_CODE, $statusCode);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getViolatedDirective(): ?string
    {
        return $this->_getData(self::VIOLATED_DIRECTIVE) === null ? null :
            (string)$this->_getData(self::VIOLATED_DIRECTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setViolatedDirective(string $violatedDirective): ReportInterface
    {
        $this->setData(self::VIOLATED_DIRECTIVE, $violatedDirective);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->_getData(self::CREATED_AT) === null ? null :
            (string)$this->_getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): ReportInterface
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->_getData(self::UPDATED_AT) === null ? null :
            (string)$this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): ReportInterface
    {
        $this->setData(self::UPDATED_AT, $updatedAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSourceFile(): ?string
    {
        return $this->_getData(self::SOURCE_FILE) === null ? null :
            (string)$this->_getData(self::SOURCE_FILE);
    }

    /**
     * @inheritdoc
     */
    public function setSourceFile(string $sourceFile): ReportInterface
    {
        $this->setData(self::SOURCE_FILE, $sourceFile);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLineNumber(): ?int
    {
        return $this->_getData(self::LINE_NUMBER) === null ? null :
            (int)$this->_getData(self::LINE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setLineNumber(int $lineNumber): ReportInterface
    {
        $this->setData(self::LINE_NUMBER, $lineNumber);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCount(): ?int
    {
        return $this->_getData(self::COUNT) === null ? null :
            (int)$this->_getData(self::COUNT);
    }

    /**
     * @inheritdoc
     */
    public function setCount(int $count): ReportInterface
    {
        $this->setData(self::COUNT, $count);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?int
    {
        return $this->_getData(self::STATUS) === null ? null :
            (int)$this->_getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): ReportInterface
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }
}
