<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Whitelist\EntryNormalizer;
use Hryvinskyi\Csp\Model\Whitelist\EntryValidator;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class EntryValidatorTest extends TestCase
{
    use CreatesWhitelistEntries;

    public function testAcceptsANormalizedEntry(): void
    {
        $entry = $this->whitelistEntry([
            'identifier' => ' CDN ',
            'policy' => 'Script-Src',
            'value' => 'HTTPS://CDN.Example.com/js/',
            'value_algorithm' => 'sha256',
            'store_id' => [2, 1, 2],
            'area' => 'Frontend',
        ]);

        $this->normalizer()->normalize($entry);

        $this->assertSame(
            ['CDN', 'script-src', 'https://cdn.example.com/js/', '', [1, 2], 'frontend'],
            [$entry->getIdentifier(), $entry->getPolicy(), $entry->getValue(), $entry->getValueAlgorithm(), $entry->getStoreIds(), $entry->getArea()]
        );
        $this->assertSame([], $this->validator()->validate($entry));
    }

    public function testAcceptsAHashEntry(): void
    {
        $digest = base64_encode(hash('sha384', 'alert(1)', true));
        $entry = $this->whitelistEntry(['value_type' => 'hash', 'value_algorithm' => 'SHA384', 'value' => "'sha384-$digest'"]);

        $this->normalizer()->normalize($entry);

        $this->assertSame([$digest, 'sha384'], [$entry->getValue(), $entry->getValueAlgorithm()]);
        $this->assertSame([], $this->validator()->validate($entry));
    }

    /**
     * @dataProvider invalidEntries
     * @param array<string, mixed> $data
     */
    public function testRejectsAnInvalidEntry(array $data): void
    {
        $this->assertCount(1, $this->validator()->validate($this->whitelistEntry($data)));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidEntries(): array
    {
        return [
            'no name' => [['identifier' => '']],
            'sub-directive' => [['policy' => 'script-src-elem']],
            'unknown directive' => [['policy' => 'navigate-to']],
            'keyword as host' => [['value' => "'unsafe-eval'"]],
            'unknown value type' => [['value_type' => 'nonce']],
            'hash without algorithm' => [['value_type' => 'hash', 'value' => base64_encode(hash('sha256', 'x', true))]],
            'unknown area' => [['area' => 'cron']],
            'unknown status' => [['status' => 3]],
            'deleted store' => [['store_id' => [1, 99]]],
            'long value' => [['value' => str_repeat('a', 250) . '.example.com']],
        ];
    }

    /**
     * @return EntryNormalizer
     */
    private function normalizer(): EntryNormalizer
    {
        return new EntryNormalizer(new SourceValueRules(new HostSourceParser()), new StoreIdList());
    }

    /**
     * @return EntryValidator
     */
    private function validator(): EntryValidator
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([
            0 => $this->createStub(StoreInterface::class),
            1 => $this->createStub(StoreInterface::class),
            2 => $this->createStub(StoreInterface::class),
        ]);

        return new EntryValidator(
            new WhitelistableDirectives(new DirectiveCatalog()),
            new SourceValueRules(new HostSourceParser()),
            $storeManager
        );
    }
}
