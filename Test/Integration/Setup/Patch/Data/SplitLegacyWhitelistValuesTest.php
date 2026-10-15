<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Setup\Patch\Data;

use Hryvinskyi\Csp\Setup\Patch\Data\SplitLegacyWhitelistValues;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class SplitLegacyWhitelistValuesTest extends TestCase
{
    use ResolvesServices;

    public function testSplitsSeveralSourcesIntoEntriesAndDisablesWhatTheRulesRefuse(): void
    {
        $this->connection()->delete($this->table('hryvinskyi_csp_whitelist'));
        $stats = $this->entry('Stats', 'connect-src', '*.stats.example.com *.stats.example.net wss://*.stats.example.com', 1, [1]);
        $existingEnabled = $this->entry('B', 'img-src', 'b.example.com', 1, [1]);
        $images = $this->entry('Images', 'img-src', 'a.example.com b.example.com', 1, [0]);
        $existingDisabled = $this->entry('F2', 'font-src', 'f2.example.com', 0, [0]);
        $fonts = $this->entry('Fonts', 'font-src', 'f1.example.com f2.example.com', 1, [1]);
        $mixed = $this->entry('Mixed', 'script-src', "cdn.example.org 'unsafe-inline'", 1, [0]);
        $keyword = $this->entry('Self', 'script-src', "'self'", 1, [0]);
        $subDirective = $this->entry('Elem', 'script-src-elem', 'x.example.com', 1, [0]);
        $valid = $this->entry('Frames', 'frame-src', 'ok.example.com', 1, [1]);

        $patch = $this->newInstance(SplitLegacyWhitelistValues::class);
        $patch->apply();
        $snapshot = $this->entries();
        $patch->apply();

        $this->assertSame($snapshot, $this->entries(), 'A second run changes nothing.');
        $byValue = [];
        foreach ($snapshot as $ruleId => $entry) {
            $byValue[$entry['policy'] . ' ' . $entry['value']] = [$ruleId, $entry['status'], $entry['stores']];
        }
        $this->assertSame([$stats, '1', ['1']], $byValue['connect-src *.stats.example.com']);
        $this->assertSame(['1', ['1']], array_slice($byValue['connect-src *.stats.example.net'], 1));
        $this->assertSame(['1', ['1']], array_slice($byValue['connect-src wss://*.stats.example.com'], 1));
        $this->assertSame([$images, '1', ['0']], $byValue['img-src a.example.com']);
        $this->assertSame([$existingEnabled, '1', ['0']], $byValue['img-src b.example.com']);
        $this->assertSame([$fonts, '1', ['1']], $byValue['font-src f1.example.com']);
        $this->assertSame([$existingDisabled, '1', ['1']], $byValue['font-src f2.example.com']);
        $this->assertSame([$mixed, '0', ['0']], $byValue["script-src cdn.example.org 'unsafe-inline'"]);
        $this->assertSame(['1', ['0']], array_slice($byValue['script-src cdn.example.org'], 1));
        $this->assertSame([$keyword, '0', ['0']], $byValue["script-src 'self'"]);
        $this->assertSame([$subDirective, '0', ['0']], $byValue['script-src-elem x.example.com']);
        $this->assertSame([$valid, '1', ['1']], $byValue['frame-src ok.example.com']);
        $this->assertCount(12, $snapshot);
    }

    public function testDoesNothingWithoutEntries(): void
    {
        $this->connection()->delete($this->table('hryvinskyi_csp_whitelist'));

        $this->newInstance(SplitLegacyWhitelistValues::class)->apply();

        $this->assertSame([], $this->entries());
    }

    /**
     * Insert an entry as 1.3.1 saved it, already linked to its stores.
     *
     * @param string $identifier
     * @param string $policy
     * @param string $value
     * @param int $status
     * @param list<int> $stores
     * @return int
     */
    private function entry(string $identifier, string $policy, string $value, int $status, array $stores): int
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_whitelist'), [
            'identifier' => $identifier,
            'policy' => $policy,
            'value_type' => 'host',
            'value' => $value,
            'status' => $status,
        ]);
        $ruleId = $this->lastInsertId();
        foreach ($stores as $storeId) {
            $this->connection()->insert(
                $this->table('hryvinskyi_csp_whitelist_store'),
                ['rule_id' => $ruleId, 'store_id' => $storeId]
            );
        }

        return $ruleId;
    }

    /**
     * Every entry by id with its directive, value, status and stores.
     *
     * @return array<int, array{policy: string|null, value: string|null, status: string|null, stores: list<string|null>}>
     */
    private function entries(): array
    {
        $entries = [];
        $select = $this->connection()->select()
            ->from($this->table('hryvinskyi_csp_whitelist'), ['rule_id', 'policy', 'value', 'status'])
            ->order('rule_id');
        foreach ($this->rows($select) as $row) {
            $ruleId = (int)($row['rule_id'] ?? 0);
            $stores = $this->connection()->select()
                ->from($this->table('hryvinskyi_csp_whitelist_store'), ['store_id'])
                ->where('rule_id = ?', $ruleId)
                ->order('store_id');
            $entries[$ruleId] = [
                'policy' => $row['policy'] ?? null,
                'value' => $row['value'] ?? null,
                'status' => $row['status'] ?? null,
                'stores' => array_column($this->rows($stores), 'store_id'),
            ];
        }

        return $entries;
    }
}
