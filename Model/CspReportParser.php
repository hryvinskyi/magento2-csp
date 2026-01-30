<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\CspReportParserInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritDoc
 */
class CspReportParser implements CspReportParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(string $json): array
    {
        $data = $this->decodeJson($json);
        $this->validateCspReportStructure($data);

        return $data['csp-report'];
    }

    /**
     * @inheritDoc
     */
    public function parseNormalized(string $json): array
    {
        $cspReport = $this->parse($json);

        return $this->normalizeKeys($cspReport);
    }

    /**
     * Decode JSON string.
     *
     * @param string $json
     * @return array
     * @throws LocalizedException
     */
    private function decodeJson(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new LocalizedException(__('Invalid JSON data: %1', $e->getMessage()));
        }

        if ($data === null) {
            throw new LocalizedException(__('Invalid JSON data'));
        }

        return $data;
    }

    /**
     * Validate that the data contains a valid csp-report structure.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateCspReportStructure(array $data): void
    {
        if (!isset($data['csp-report']) || !is_array($data['csp-report'])) {
            throw new LocalizedException(__('Invalid CSP report data'));
        }

        $cspReport = $data['csp-report'];

        if (!isset($cspReport['effective-directive'], $cspReport['blocked-uri'])) {
            throw new LocalizedException(__('Invalid CSP report data: missing required fields'));
        }
    }

    /**
     * Normalize hyphenated keys to underscored keys.
     *
     * @param array $data
     * @return array
     */
    private function normalizeKeys(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[str_replace('-', '_', $key)] = $value;
        }

        return $normalized;
    }
}
