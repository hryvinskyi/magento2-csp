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
 * "Select All" in the report grid of one group reaches only that group's reports.
 *
 * The grid limits itself to a group by a page parameter that the mass action request does not carry; the action URL
 * names the group instead, and a request that names none is refused.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ReportMassActionTest extends AbstractBackendController
{
    use ResolvesServices;

    public function testSelectAllInAGroupGridDeletesOnlyThatGroupsReports(): void
    {
        $first = $this->recordGroupWithReports('one.example.org', 2);
        $second = $this->recordGroupWithReports('two.example.org', 1);

        $this->selectAll('backend/hryvinskyi_csp/report/massDelete/report_group/' . $first);

        $this->assertSame([$first => 0, $second => 1], $this->reportCounts([$first, $second]));
        $this->assertSessionMessages(
            $this->containsEqual('A total of 2 record(s) have been deleted.'),
            MessageInterface::TYPE_SUCCESS
        );
    }

    public function testSelectAllInTheGridOfEveryGroupReachesEveryGroup(): void
    {
        $first = $this->recordGroupWithReports('one.example.org', 1);
        $second = $this->recordGroupWithReports('two.example.org', 1);

        $this->selectAll('backend/hryvinskyi_csp/report/massDelete/report_group/all');

        $this->assertSame([$first => 0, $second => 0], $this->reportCounts([$first, $second]));
    }

    /**
     * @dataProvider unscopedActions
     * @param string $uri
     */
    public function testAMassActionThatNamesNoGroupChangesNothing(string $uri): void
    {
        $first = $this->recordGroupWithReports('one.example.org', 1);
        $second = $this->recordGroupWithReports('two.example.org', 1);

        $this->selectAll($uri);

        $this->assertSame([$first => 1, $second => 1], $this->reportCounts([$first, $second]));
        $this->assertSessionMessages(
            $this->containsEqual('The report grid did not say which report group it shows. Reload the grid and try again.'),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unscopedActions(): array
    {
        return [
            'delete' => ['backend/hryvinskyi_csp/report/massDelete'],
            'convert' => ['backend/hryvinskyi_csp/report/massConvert'],
        ];
    }

    public function testSelectAllConvertsOnlyTheGroupTheGridShows(): void
    {
        $first = $this->recordGroupWithReports('one.example.org', 1);
        $second = $this->recordGroupWithReports('two.example.org', 1);

        $this->selectAll('backend/hryvinskyi_csp/report/massConvert/report_group/' . $first);

        $groups = $this->rows($this->connection()->select()
            ->from($this->table('hryvinskyi_csp_violation_report_group'), ['group_id'])
            ->where('group_id IN (?)', [$first, $second]));
        $this->assertSame([['group_id' => (string)$second]], $groups);
    }

    /**
     * POST a mass action with every row of the report grid selected.
     *
     * @param string $uri
     * @return void
     */
    private function selectAll(string $uri): void
    {
        $request = $this->getRequest();
        if (!$request instanceof HttpRequest) {
            throw new \LogicException('The test request is not an HTTP request.');
        }
        $request->setMethod(HttpRequest::METHOD_POST);
        $request->setPostValue(['namespace' => 'hryvinskyi_csp_report_listing', 'excluded' => 'false']);

        $this->dispatch($uri);
    }

    /**
     * Insert a storefront script-src group for the host in store 1, with reports.
     *
     * @param string $host
     * @param int $reports
     * @return int
     */
    private function recordGroupWithReports(string $host, int $reports): int
    {
        $this->connection()->insert($this->table('hryvinskyi_csp_violation_report_group'), [
            'policy' => 'script-src', 'value' => $host, 'store_id' => 1, 'area' => 'frontend',
        ]);
        $groupId = $this->lastInsertId();
        for ($report = 1; $report <= $reports; $report++) {
            $this->connection()->insert($this->table('hryvinskyi_csp_violation_report'), [
                'group_id' => $groupId,
                'blocked_uri' => sprintf('https://%s/%d.js', $host, $report),
                'document_uri' => 'http://localhost/',
                'effective_directive' => 'script-src-elem',
            ]);
        }

        return $groupId;
    }

    /**
     * Number of reports left in each group.
     *
     * @param list<int> $groupIds
     * @return array<int, int>
     */
    private function reportCounts(array $groupIds): array
    {
        $counts = [];
        foreach ($groupIds as $groupId) {
            $count = $this->connection()->fetchOne(
                $this->connection()->select()
                    ->from($this->table('hryvinskyi_csp_violation_report'), ['COUNT(*)'])
                    ->where('group_id = ?', $groupId)
            );
            $counts[$groupId] = is_numeric($count) ? (int)$count : -1;
        }

        return $counts;
    }
}
