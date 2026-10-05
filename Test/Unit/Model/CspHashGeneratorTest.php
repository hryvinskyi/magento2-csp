<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model;

use Hryvinskyi\Csp\Model\CspHashGenerator;
use PHPUnit\Framework\TestCase;

class CspHashGeneratorTest extends TestCase
{
    /**
     * Digests a browser computes for the element text `alert(1);\nconsole.log(2);` and for an empty script.
     *
     * @dataProvider scripts
     * @param string $script
     * @param string $expected
     */
    public function testMatchesTheBrowserDigest(string $script, string $expected): void
    {
        $this->assertSame($expected, (new CspHashGenerator())->execute($script));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function scripts(): array
    {
        $digest = 'ulSR2mFrdWVXSgeR4TBKNBoRfFVHQ+DXt7P2Ek1YyPo=';

        return [
            'LF' => ["alert(1);\nconsole.log(2);", $digest],
            'CRLF is normalised by the HTML parser' => ["alert(1);\r\nconsole.log(2);", $digest],
            'CR is normalised by the HTML parser' => ["alert(1);\rconsole.log(2);", $digest],
            'empty' => ['', '47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU='],
        ];
    }

    public function testHashesMultibyteTextAsItsUtf8Bytes(): void
    {
        $this->assertSame(
            base64_encode(hash('sha256', "console.log('Привіт');", true)),
            (new CspHashGenerator())->execute("console.log('Привіт');")
        );
    }
}
