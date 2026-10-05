<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Controller;

use Laminas\Http\Headers;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class ReportEndpointTest extends AbstractController
{
    /**
     * @magentoConfigFixture default/csp/general/enabled_reports 1
     */
    public function testTheBareUrlAcceptsAReportWithAnEmptyNoContentResponse(): void
    {
        $request = $this->httpRequest();
        $request->setMethod(HttpRequest::METHOD_POST);
        $request->setHeaders(Headers::fromString('Content-Type: application/csp-report'));
        $request->setContent((string)json_encode(['csp-report' => [
            'document-uri' => 'http://localhost/index.php/',
            'blocked-uri' => 'https://cdn.example.org/a.js',
            'effective-directive' => 'script-src-elem',
        ]]));

        $this->dispatch('csp_report_watch');

        $response = $this->getResponse();
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame(204, $response->getHttpResponseCode());
        $this->assertSame('', $response->getContent());
    }

    public function testRefusesGet(): void
    {
        $this->httpRequest()->setMethod(HttpRequest::METHOD_GET);

        $this->dispatch('csp_report_watch/admin/index');

        $this->assert404NotFound();
    }

    /**
     * @return HttpRequest
     */
    private function httpRequest(): HttpRequest
    {
        $request = $this->getRequest();
        if (!$request instanceof HttpRequest) {
            throw new \LogicException('The test request is not an HTTP request.');
        }

        return $request;
    }
}
