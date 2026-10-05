<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Controller\Adminhtml;

use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Framework\Acl\Builder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Every admin action is refused when the admin lacks one of the resources it needs, and changes only accept POST.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AclTest extends AbstractBackendController
{
    use ResolvesServices;

    /**
     * @dataProvider protectedActions
     * @param string $uri
     * @param string $method
     * @param string $deniedResource
     */
    public function testActionIsRefusedWithoutItsResource(string $uri, string $method, string $deniedResource): void
    {
        $this->service(Builder::class)->getAcl()->deny(null, $deniedResource);
        $this->httpRequest()->setMethod($method);

        $this->dispatch($uri);

        $response = $this->getResponse();
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame(403, $response->getHttpResponseCode());
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function protectedActions(): array
    {
        $post = HttpRequest::METHOD_POST;
        $get = HttpRequest::METHOD_GET;

        return [
            'whitelist grid' => ['backend/hryvinskyi_csp/whitelist/index', $get, 'Hryvinskyi_Csp::whitelist'],
            'whitelist save' => ['backend/hryvinskyi_csp/whitelist/save', $post, 'Hryvinskyi_Csp::whitelist_save'],
            'whitelist inline edit' => ['backend/hryvinskyi_csp/whitelist/inlineEdit', $post, 'Hryvinskyi_Csp::whitelist_save'],
            'whitelist delete' => ['backend/hryvinskyi_csp/whitelist/delete', $post, 'Hryvinskyi_Csp::whitelist_delete'],
            'whitelist mass delete' => ['backend/hryvinskyi_csp/whitelist/massDelete', $post, 'Hryvinskyi_Csp::whitelist_delete'],
            'report groups' => ['backend/hryvinskyi_csp/reportgroup/index', $get, 'Hryvinskyi_Csp::reports'],
            'group status' => ['backend/hryvinskyi_csp/reportgroup/changeStatus', $post, 'Hryvinskyi_Csp::reports_manage'],
            'group delete' => ['backend/hryvinskyi_csp/reportgroup/delete', $post, 'Hryvinskyi_Csp::reports_manage'],
            'group convert without convert' => ['backend/hryvinskyi_csp/reportgroup/convertToWhitelist', $post, 'Hryvinskyi_Csp::reports_convert'],
            'group convert without whitelist save' => ['backend/hryvinskyi_csp/reportgroup/convertToWhitelist', $post, 'Hryvinskyi_Csp::whitelist_save'],
            'group mass convert without whitelist save' => ['backend/hryvinskyi_csp/reportgroup/massConvert', $post, 'Hryvinskyi_Csp::whitelist_save'],
            'reports' => ['backend/hryvinskyi_csp/report/index', $get, 'Hryvinskyi_Csp::reports'],
            'report delete' => ['backend/hryvinskyi_csp/report/delete', $post, 'Hryvinskyi_Csp::reports_manage'],
            'report convert without whitelist save' => ['backend/hryvinskyi_csp/report/convertToWhitelist', $post, 'Hryvinskyi_Csp::whitelist_save'],
        ];
    }

    /**
     * @dataProvider postOnlyActions
     * @param string $uri
     */
    public function testChangesRefuseGet(string $uri): void
    {
        $this->httpRequest()->setMethod(HttpRequest::METHOD_GET);

        $this->dispatch($uri);

        $this->assert404NotFound();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function postOnlyActions(): array
    {
        return [
            'whitelist delete' => ['backend/hryvinskyi_csp/whitelist/delete/id/1'],
            'group status' => ['backend/hryvinskyi_csp/reportgroup/changeStatus/id/1/status/1'],
            'group convert' => ['backend/hryvinskyi_csp/reportgroup/convertToWhitelist/id/1'],
            'report delete' => ['backend/hryvinskyi_csp/report/delete/id/1'],
        ];
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
