<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Controller\Adminhtml;

use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Saving a new whitelist entry from the admin form when the form sends no store views.
 *
 * In single-store mode the form hides the store view picker, so the request carries no store ids; the entry then
 * applies to every store. With several stores running, a request without store ids is still refused.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class WhitelistSaveTest extends AbstractBackendController
{
    use ResolvesServices;

    private const VALUE = 'single-store.example.org';

    /**
     * @magentoConfigFixture current_store general/single_store_mode/enabled 1
     */
    public function testANewEntryWithoutStoreViewsAppliesToEveryStoreInSingleStoreMode(): void
    {
        $this->saveFormWithoutStoreViews();

        $this->assertSessionMessages(
            $this->containsEqual('The whitelist entry has been saved.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSame([['store_id' => '0']], $this->storesOfTheSavedEntry());
    }

    /**
     * @magentoConfigFixture current_store general/single_store_mode/enabled 0
     */
    public function testANewEntryWithoutStoreViewsIsRefusedWhenSingleStoreModeIsOff(): void
    {
        $this->saveFormWithoutStoreViews();

        $this->assertSessionMessages(
            $this->containsEqual('Choose the store views the entry applies to, or All Store Views.'),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSame([], $this->rows($this->connection()->select()
            ->from($this->table('hryvinskyi_csp_whitelist'), ['rule_id'])
            ->where('value = ?', self::VALUE)));
    }

    /**
     * POST the whitelist form for a new storefront host entry, without the store view field.
     *
     * @return void
     */
    private function saveFormWithoutStoreViews(): void
    {
        $request = $this->getRequest();
        if (!$request instanceof HttpRequest) {
            throw new \LogicException('The test request is not an HTTP request.');
        }
        $request->setMethod(HttpRequest::METHOD_POST);
        $request->setPostValue([
            'identifier' => 'Single store',
            'policy' => 'img-src',
            'value_type' => 'host',
            'value' => self::VALUE,
            'area' => 'frontend',
            'status' => '1',
        ]);

        $this->dispatch('backend/hryvinskyi_csp/whitelist/save');
    }

    /**
     * Store links of the entries saved for the test value.
     *
     * @return list<array<string, string|null>>
     */
    private function storesOfTheSavedEntry(): array
    {
        return $this->rows($this->connection()->select()
            ->from(['whitelist' => $this->table('hryvinskyi_csp_whitelist')], [])
            ->join(
                ['store' => $this->table('hryvinskyi_csp_whitelist_store')],
                'store.rule_id = whitelist.rule_id',
                ['store_id']
            )
            ->where('whitelist.value = ?', self::VALUE));
    }
}
