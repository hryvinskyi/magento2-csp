<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\UiComponent\Listing\Column;

use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

/**
 * Reads the row actions an actions column produces, and checks how the grid will send them.
 *
 * The core actions column attaches its click handler only to an action with a confirm, a callback or no href;
 * any other action is rendered as a plain link and opened with GET, whatever its post flag says. A POST-only
 * controller then refuses the request with a 404, so every posted action of these columns carries a confirm.
 *
 * @mixin TestCase
 */
trait RowActionsAssertions
{
    /**
     * Assert that every action flagged post has a confirm with a title and a message.
     *
     * @param array<mixed> $actions
     * @return void
     */
    private function assertEveryPostedActionHasAConfirmation(array $actions): void
    {
        foreach ($this->postedActionNames($actions) as $name) {
            $confirm = $this->action($actions, $name)['confirm'] ?? null;
            $this->assertIsArray($confirm, sprintf('Posted action "%s" has no confirm, so it is sent as GET.', $name));
            $this->assertNotSame('', $this->text($confirm['title'] ?? null), sprintf('"%s" has no title.', $name));
            $this->assertNotSame('', $this->text($confirm['message'] ?? null), sprintf('"%s" has no message.', $name));
        }
    }

    /**
     * Names of the actions flagged post, in their order.
     *
     * @param array<mixed> $actions
     * @return list<string>
     */
    private function postedActionNames(array $actions): array
    {
        $names = [];
        foreach ($actions as $name => $action) {
            if (is_string($name) && is_array($action) && ($action['post'] ?? false) === true) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Actions of the first row of a prepared data source.
     *
     * @param array<mixed> $dataSource
     * @param string $column
     * @return array<mixed>
     */
    private function rowActions(array $dataSource, string $column): array
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? ($data['items'] ?? null) : null;
        $row = is_array($items) ? ($items[0] ?? null) : null;
        $actions = is_array($row) ? ($row[$column] ?? null) : null;
        $this->assertIsArray($actions);

        return $actions;
    }

    /**
     * One action of a row.
     *
     * @param array<mixed> $actions
     * @param string $name
     * @return array<mixed>
     */
    private function action(array $actions, string $name): array
    {
        $action = $actions[$name] ?? null;
        $this->assertIsArray($action, sprintf('The row has no "%s" action.', $name));

        return $action;
    }

    /**
     * Href of one action of a row.
     *
     * @param array<mixed> $actions
     * @param string $name
     * @return string
     */
    private function href(array $actions, string $name): string
    {
        return $this->text($this->action($actions, $name)['href'] ?? null);
    }

    /**
     * URL builder that spells a route and its parameters as a query string.
     *
     * @return UrlInterface
     */
    private function urlBuilder(): UrlInterface
    {
        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (?string $route = null, ?array $params = null): string
                => (string)$route . '?' . http_build_query($params ?? [])
        );

        return $urlBuilder;
    }

    /**
     * A label, phrase or URL as text.
     *
     * @param mixed $value
     * @return string
     */
    private function text(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string)$value : '';
    }
}
