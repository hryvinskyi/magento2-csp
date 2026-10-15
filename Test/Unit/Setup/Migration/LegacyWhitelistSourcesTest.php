<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Setup\Migration;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Setup\Migration\LegacyWhitelistSources;
use PHPUnit\Framework\TestCase;

class LegacyWhitelistSourcesTest extends TestCase
{
    /**
     * @dataProvider entries
     * @param array{string, string, string, string} $entry
     * @param array{split: bool, allowed: list<string>, refused: list<string>} $expected
     */
    public function testTellsTheSourcesAnEntryPutIntoThePolicy(array $entry, array $expected): void
    {
        $sources = new LegacyWhitelistSources(
            new WhitelistableDirectives(new DirectiveCatalog()),
            new SourceValueRules(new HostSourceParser())
        );

        $this->assertSame($expected, $sources->sources(...$entry));
    }

    /**
     * @return array<string, array{array{string, string, string, string}, array{split: bool, allowed: list<string>, refused: list<string>}}>
     */
    public static function entries(): array
    {
        $digest = base64_encode(hash('sha256', 'alert(1)', true));

        return [
            'one valid source' => [
                ['img-src', 'host', '', 'cdn.example.com'],
                ['split' => false, 'allowed' => ['cdn.example.com'], 'refused' => []],
            ],
            'several sources, as 1.x put them into the header' => [
                ['connect-src', 'host', '', '*.stats.example.com *.stats.example.net  wss://*.stats.example.com'],
                ['split' => true, 'allowed' => ['*.stats.example.com', '*.stats.example.net', 'wss://*.stats.example.com'], 'refused' => []],
            ],
            'words normalized and repeated words allowed once' => [
                ['img-src', 'host', '', "HTTPS://CDN.Example.com\tcdn.example.org https://cdn.example.com"],
                ['split' => true, 'allowed' => ['https://cdn.example.com', 'cdn.example.org'], 'refused' => []],
            ],
            'a keyword among the sources' => [
                ['script-src', 'host', '', "cdn.example.com 'unsafe-inline' *"],
                ['split' => true, 'allowed' => ['cdn.example.com'], 'refused' => ["'unsafe-inline'", '*']],
            ],
            'a keyword alone' => [
                ['script-src', 'host', '', "'self'"],
                ['split' => false, 'allowed' => [], 'refused' => ["'self'"]],
            ],
            'a directive that cannot be whitelisted' => [
                ['script-src-elem', 'host', '', 'cdn.example.com'],
                ['split' => false, 'allowed' => [], 'refused' => ['cdn.example.com']],
            ],
            'several sources under a directive that cannot be whitelisted' => [
                ['navigate-to', 'host', '', 'a.example.com b.example.com'],
                ['split' => true, 'allowed' => [], 'refused' => ['a.example.com', 'b.example.com']],
            ],
            'hashes split and stripped of their prefix' => [
                ['script-src', 'hash', 'sha256', "'sha256-$digest' bad"],
                ['split' => true, 'allowed' => [$digest], 'refused' => ['bad']],
            ],
        ];
    }
}
