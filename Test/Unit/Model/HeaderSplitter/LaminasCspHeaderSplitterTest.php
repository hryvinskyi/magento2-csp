<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\HeaderSplitter;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Model\HeaderSplitter\LaminasCspHeaderSplitter;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LaminasCspHeaderSplitterTest extends TestCase
{
    public function testSplitsHeaderWhenExceedsMaxSize(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getMaxHeaderSize')->willReturn(50);
        $logger = $this->createMock(LoggerInterface::class);

        $splitter = new LaminasCspHeaderSplitter($config, $logger);

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())->method('clearHeader');
        $response->expects($this->exactly(2))->method('setHeader');

        $headerValue = "default-src 'self'; script-src 'self' example.com; style-src 'self' styles.example.com; report-uri /csp_report";

        $splitter->splitHeader($response, 'Content-Security-Policy', $headerValue);
    }

    public function testDoesNotSplitHeaderBelowMaxSize(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getMaxHeaderSize')->willReturn(1000);
        $logger = $this->createMock(LoggerInterface::class);

        $splitter = new LaminasCspHeaderSplitter($config, $logger);

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->never())->method('clearHeader');
        $response->expects($this->never())->method('setHeader');

        $headerValue = "default-src 'self'; script-src 'self'";

        $splitter->splitHeader($response, 'Content-Security-Policy', $headerValue);
    }
}