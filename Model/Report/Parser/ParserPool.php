<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\Parser;

use Hryvinskyi\Csp\Api\ViolationReportParserInterface;

/**
 * Picks the report parser for a request's content type.
 */
class ParserPool
{
    /**
     * @param array<string, ViolationReportParserInterface> $parsers
     */
    public function __construct(private readonly array $parsers = [])
    {
    }

    /**
     * Parser of the content type, parameters such as `charset` ignored; null when none reads it.
     *
     * @param string $contentType
     * @return ViolationReportParserInterface|null
     */
    public function forContentType(string $contentType): ?ViolationReportParserInterface
    {
        $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
        foreach ($this->parsers as $parser) {
            if ($parser->supports($mediaType)) {
                return $parser;
            }
        }

        return null;
    }
}
