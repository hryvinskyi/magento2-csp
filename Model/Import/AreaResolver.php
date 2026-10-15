<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Config\Source\Area as AreaOptions;
use Magento\Framework\Phrase;

/**
 * Area named in an imported row: its code (`all`, `frontend`, `adminhtml`) or the label the grid shows and its export
 * writes, in English or in the admin's language, compared without regard to case.
 */
class AreaResolver
{
    /**
     * @param AreaOptions $areaOptions
     */
    public function __construct(private readonly AreaOptions $areaOptions)
    {
    }

    /**
     * Area the text names, or null when it names none.
     *
     * @param string $text
     * @return Area|null
     */
    public function resolve(string $text): ?Area
    {
        $wanted = mb_strtolower(trim($text));
        $area = Area::tryFrom($wanted);
        if ($area !== null) {
            return $area;
        }
        foreach ($this->areaOptions->toOptionArray() as $option) {
            if (in_array($wanted, $this->labels($option['label']), true)) {
                return Area::tryFrom($option['value']);
            }
        }

        return null;
    }

    /**
     * Lower-case forms of an option label: as shown in the admin's language, and in English.
     *
     * @param Phrase $label
     * @return list<string>
     */
    private function labels(Phrase $label): array
    {
        return [mb_strtolower($label->render()), mb_strtolower($label->getText())];
    }
}
