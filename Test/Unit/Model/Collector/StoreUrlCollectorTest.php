<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Collector;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\PolicyCollectionMergerInterface;
use Hryvinskyi\Csp\Model\Collector\StoreUrlCollector;
use Magento\Framework\Url\ScopeInterface;
use Magento\Framework\Url\ScopeResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Csp\Model\Collector\StoreUrlCollector
 */
class StoreUrlCollectorTest extends TestCase
{
    private StoreUrlCollector $collector;
    private MockObject|ScopeResolverInterface $scopeResolverMock;
    private MockObject|ConfigInterface $configMock;
    private MockObject|PolicyCollectionMergerInterface $mergerMock;

    protected function setUp(): void
    {
        $this->scopeResolverMock = $this->createMock(ScopeResolverInterface::class);
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->mergerMock = $this->createMock(PolicyCollectionMergerInterface::class);

        $scopeMock = $this->createMock(ScopeInterface::class);
        $scopeMock->method('getBaseUrl')->willReturn('https://example.com/');
        $this->scopeResolverMock->method('getScopes')->willReturn([$scopeMock]);

        $this->collector = new StoreUrlCollector(
            $this->scopeResolverMock,
            $this->configMock,
            $this->mergerMock
        );
    }

    // ==================== Disabled Tests ====================

    public function testCollectReturnsDefaultPoliciesWhenDisabled(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(false);

        $this->mergerMock->expects($this->never())->method('mergeOrAdd');

        $defaultPolicies = ['existing-policy'];
        $result = $this->collector->collect($defaultPolicies);

        $this->assertSame($defaultPolicies, $result);
    }

    // ==================== Directive Targeting Tests ====================

    public function testCollectOnlyTargetsRelevantDirectives(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(true);

        $expectedDirectives = [
            'default-src',
            'script-src',
            'style-src',
            'img-src',
            'font-src',
            'connect-src',
            'media-src',
        ];

        $calledDirectives = [];
        $this->mergerMock->expects($this->exactly(7))
            ->method('mergeOrAdd')
            ->willReturnCallback(function ($policies, $directive, $policy) use (&$calledDirectives) {
                $calledDirectives[] = $directive;
                return $policies;
            });

        $this->collector->collect([]);

        $this->assertSame($expectedDirectives, $calledDirectives);
    }

    public function testCollectDoesNotTargetBaseUri(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(true);

        $calledDirectives = [];
        $this->mergerMock->method('mergeOrAdd')
            ->willReturnCallback(function ($policies, $directive, $policy) use (&$calledDirectives) {
                $calledDirectives[] = $directive;
                return $policies;
            });

        $this->collector->collect([]);

        $this->assertNotContains('base-uri', $calledDirectives);
    }

    public function testCollectDoesNotTargetFrameAncestors(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(true);

        $calledDirectives = [];
        $this->mergerMock->method('mergeOrAdd')
            ->willReturnCallback(function ($policies, $directive, $policy) use (&$calledDirectives) {
                $calledDirectives[] = $directive;
                return $policies;
            });

        $this->collector->collect([]);

        $this->assertNotContains('frame-ancestors', $calledDirectives);
    }

    public function testCollectDoesNotTargetFormAction(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(true);

        $calledDirectives = [];
        $this->mergerMock->method('mergeOrAdd')
            ->willReturnCallback(function ($policies, $directive, $policy) use (&$calledDirectives) {
                $calledDirectives[] = $directive;
                return $policies;
            });

        $this->collector->collect([]);

        $this->assertNotContains('form-action', $calledDirectives);
    }

    public function testCollectDoesNotTargetObjectSrc(): void
    {
        $this->configMock->method('isAddAllStorefrontUrls')->willReturn(true);

        $calledDirectives = [];
        $this->mergerMock->method('mergeOrAdd')
            ->willReturnCallback(function ($policies, $directive, $policy) use (&$calledDirectives) {
                $calledDirectives[] = $directive;
                return $policies;
            });

        $this->collector->collect([]);

        $this->assertNotContains('object-src', $calledDirectives);
    }
}
