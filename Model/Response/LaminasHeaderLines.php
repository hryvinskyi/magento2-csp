<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Response;

use Hryvinskyi\Csp\Api\ResponseHeaderLinesInterface;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Header\MultipleHeaderInterface;
use Laminas\Loader\PluginClassLoader;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Header lines of a Laminas response, which Magento's HTTP response is.
 *
 * Laminas sends a header with `header($line)`, which replaces an earlier line of the same name, unless the header's
 * class is a multiple header. A header can carry several lines once its multiple-header class, configured in
 * `multipleHeaderClasses` by lower-case header name, is registered on the response.
 */
class LaminasHeaderLines implements ResponseHeaderLinesInterface
{
    /**
     * @param array<string, class-string<MultipleHeaderInterface>> $multipleHeaderClasses
     */
    public function __construct(private readonly array $multipleHeaderClasses = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function values(HttpResponse $response, string $headerName): array
    {
        if (!$response instanceof AbstractMessage) {
            $header = $response->getHeader($headerName);

            return $header instanceof HeaderInterface ? [$header->getFieldValue()] : [];
        }

        return array_map(
            static fn (HeaderInterface $header): string => $header->getFieldValue(),
            $this->lines($response, $headerName)
        );
    }

    /**
     * @inheritDoc
     */
    public function allowSeveralLines(HttpResponse $response, string $headerName): bool
    {
        $class = $this->multipleHeaderClasses[strtolower($headerName)] ?? null;
        if ($class === null || !$response instanceof AbstractMessage) {
            return false;
        }

        $loader = $response->getHeaders()->getPluginClassLoader();
        if (!$loader instanceof PluginClassLoader) {
            return false;
        }
        $loader->registerPlugin(str_replace(['-', '_', ' ', '.'], '', strtolower($headerName)), $class);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function replace(HttpResponse $response, string $headerName, array $values): bool
    {
        $response->clearHeader($headerName);
        foreach ($values as $value) {
            $response->setHeader($headerName, $value, false);
        }
        if (count($values) < 2) {
            return true;
        }
        if (!$response instanceof AbstractMessage) {
            return false;
        }

        $lines = $this->lines($response, $headerName);
        $multiple = array_filter($lines, static fn (HeaderInterface $line): bool => $line instanceof MultipleHeaderInterface);

        return count($multiple) === count($values) && count($lines) === count($values);
    }

    /**
     * Lines of the header the response carries.
     *
     * @param AbstractMessage $response
     * @param string $headerName
     * @return list<HeaderInterface>
     */
    private function lines(AbstractMessage $response, string $headerName): array
    {
        $lines = [];
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof HeaderInterface && strcasecmp($header->getFieldName(), $headerName) === 0) {
                $lines[] = $header;
            }
        }

        return $lines;
    }
}
