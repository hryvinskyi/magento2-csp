<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Reads and writes the lines of a header on a response.
 *
 * @api
 */
interface ResponseHeaderLinesInterface
{
    /**
     * Values of every line of the header, in order.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @return list<string>
     */
    public function values(HttpResponse $response, string $headerName): array;

    /**
     * Prepare the response to send the header on several lines; false when it cannot.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @return bool
     */
    public function allowSeveralLines(HttpResponse $response, string $headerName): bool;

    /**
     * Replace the header with one line per value; false when the response would not send each value as its own line.
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param list<string> $values
     * @return bool
     */
    public function replace(HttpResponse $response, string $headerName, array $values): bool;
}
