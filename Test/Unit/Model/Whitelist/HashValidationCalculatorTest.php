<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;
use Hryvinskyi\Csp\Model\Config\Source\HashValidationOptions;
use Hryvinskyi\Csp\Model\CspHashGenerator;
use Hryvinskyi\Csp\Model\Whitelist\HashValidationCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Csp\Model\Whitelist\HashValidationCalculator
 */
class HashValidationCalculatorTest extends TestCase
{
    private HashValidationCalculator $calculator;
    private CspHashGeneratorInterface $hashGenerator;

    protected function setUp(): void
    {
        $this->hashGenerator = new CspHashGenerator();
        $this->calculator = new HashValidationCalculator($this->hashGenerator);
    }

    // ==================== Basic Tests ====================

    public function testCalculateForItemsEmptyArray(): void
    {
        $result = $this->calculator->calculateForItems([]);
        $this->assertSame([], $result);
    }

    public function testCalculateForItemsNonHashType(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'host',
                'value' => 'example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_APPLICABLE, $result[0]['hash_validation']);
    }

    public function testCalculateForItemsNonSha256Algorithm(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha384',
                'value' => 'somehash',
                'script_content' => 'console.log("test");',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_APPLICABLE, $result[0]['hash_validation']);
    }

    // ==================== Not Verified Tests ====================

    public function testCalculateForItemsEmptyScriptContent(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => 'somehash',
                'script_content' => '',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_VERIFIED, $result[0]['hash_validation']);
    }

    public function testCalculateForItemsNullScriptContent(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => 'somehash',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_VERIFIED, $result[0]['hash_validation']);
    }

    public function testCalculateForItemsEmptyStoredHash(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => '',
                'script_content' => 'console.log("test");',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_VERIFIED, $result[0]['hash_validation']);
    }

    // ==================== Valid Hash Tests ====================

    public function testCalculateForItemsValidHash(): void
    {
        $scriptContent = 'console.log("test");';
        $validHash = $this->hashGenerator->execute($scriptContent);

        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => $validHash,
                'script_content' => $scriptContent,
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::VALID, $result[0]['hash_validation']);
    }

    public function testCalculateForItemsValidHashWithLineEndings(): void
    {
        // Script with different line endings should still validate
        $scriptContent = "console.log(\"test\");\nconsole.log(\"test2\");";
        $validHash = $this->hashGenerator->execute($scriptContent);

        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => $validHash,
                'script_content' => $scriptContent,
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::VALID, $result[0]['hash_validation']);
    }

    // ==================== Invalid Hash Tests ====================

    public function testCalculateForItemsInvalidHash(): void
    {
        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => 'invalidhash123',
                'script_content' => 'console.log("test");',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::INVALID, $result[0]['hash_validation']);
    }

    public function testCalculateForItemsHashMismatch(): void
    {
        $scriptContent1 = 'console.log("original");';
        $scriptContent2 = 'console.log("modified");';
        $hashForOriginal = $this->hashGenerator->execute($scriptContent1);

        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => $hashForOriginal,
                'script_content' => $scriptContent2, // Different content
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::INVALID, $result[0]['hash_validation']);
    }

    // ==================== Mixed Items Tests ====================

    public function testCalculateForItemsMixedTypes(): void
    {
        $validScript = 'console.log("valid");';
        $validHash = $this->hashGenerator->execute($validScript);

        $items = [
            [
                'rule_id' => 1,
                'value_type' => 'host',
                'value' => 'example.com',
            ],
            [
                'rule_id' => 2,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => $validHash,
                'script_content' => $validScript,
            ],
            [
                'rule_id' => 3,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => 'invalidhash',
                'script_content' => 'some script',
            ],
            [
                'rule_id' => 4,
                'value_type' => 'hash',
                'value_algorithm' => 'sha256',
                'value' => 'somehash',
                'script_content' => '',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(HashValidationOptions::NOT_APPLICABLE, $result[0]['hash_validation']);
        $this->assertSame(HashValidationOptions::VALID, $result[1]['hash_validation']);
        $this->assertSame(HashValidationOptions::INVALID, $result[2]['hash_validation']);
        $this->assertSame(HashValidationOptions::NOT_VERIFIED, $result[3]['hash_validation']);
    }
}
