<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\App\Emulation;

/**
 * Renders the content of a store view as its storefront does and finds the inline scripts in it.
 *
 * A console command has no area, so the work runs in an emulated frontend area and store environment.
 */
class StoreContentScanner
{
    /**
     * @param State $appState
     * @param Emulation $emulation
     * @param InlineScriptExtractor $extractor
     * @param FormKey $formKey
     * @param array<string, ScriptSourceInterface> $sources Source code => source
     */
    public function __construct(
        private readonly State $appState,
        private readonly Emulation $emulation,
        private readonly InlineScriptExtractor $extractor,
        private readonly FormKey $formKey,
        private readonly array $sources = []
    ) {
    }

    /**
     * Codes of the available sources.
     *
     * @return list<string>
     */
    public function sourceCodes(): array
    {
        return array_keys($this->sources);
    }

    /**
     * Inline scripts of the store view's content in the given sources, per entity.
     *
     * @param int $storeId
     * @param list<string> $sourceCodes
     * @return list<array{label: string, scripts: list<string>, skipped: list<string>, warning: string|null}>
     * @throws \InvalidArgumentException For an unknown source code
     */
    public function scan(int $storeId, array $sourceCodes): array
    {
        $sources = [];
        foreach ($sourceCodes as $code) {
            $sources[] = $this->sources[$code]
                ?? throw new \InvalidArgumentException(sprintf('Unknown content type "%s". Use one of: %s.', $code, implode(', ', $this->sourceCodes())));
        }

        $entities = [];
        $this->appState->emulateAreaCode(Area::AREA_FRONTEND, function () use ($storeId, $sources, &$entities): void {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            try {
                $entities = $this->scanSources($storeId, $sources);
            } finally {
                $this->emulation->stopEnvironmentEmulation();
            }
        });

        return $entities;
    }

    /**
     * @param int $storeId
     * @param list<ScriptSourceInterface> $sources
     * @return list<array{label: string, scripts: list<string>, skipped: list<string>, warning: string|null}>
     */
    private function scanSources(int $storeId, array $sources): array
    {
        $perRequestValues = [$this->formKey->getFormKey()];
        $entities = [];
        foreach ($sources as $source) {
            foreach ($source->contents($storeId) as $content) {
                $found = $this->extractor->extract($content['html'], $perRequestValues);
                $entities[] = [
                    'label' => $content['label'],
                    'scripts' => $found['scripts'],
                    'skipped' => $found['skipped'],
                    'warning' => $content['warning'],
                ];
            }
        }

        return $entities;
    }
}
