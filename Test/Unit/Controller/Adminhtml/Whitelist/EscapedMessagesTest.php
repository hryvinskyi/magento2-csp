<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\Whitelist\Delete;
use Hryvinskyi\Csp\Controller\Adminhtml\Whitelist\Edit;
use Hryvinskyi\Csp\Controller\Adminhtml\Whitelist\Import;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Hryvinskyi\Csp\Model\Import\WhitelistImporter;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Whitelist actions tell the admin what happened through escaped messages, whatever text an exception carries.
 */
class EscapedMessagesTest extends TestCase
{
    /**
     * @var list<array{string, string}>
     */
    private array $messages = [];

    public function testDeleteEscapesTheReasonItFailed(): void
    {
        $repository = $this->createStub(WhitelistRepositoryInterface::class);
        $repository->method('deleteById')->willThrowException(
            new LocalizedException(__('Entry <img src=x onerror=alert(1)> is locked.'))
        );

        (new Delete($this->context(['id' => '5']), $repository, $this->escapedMessages()))->execute();

        $this->assertSame(
            [['error', 'Entry &lt;img src=x onerror=alert(1)&gt; is locked.']],
            $this->messages
        );
    }

    public function testDeleteConfirmsADeletion(): void
    {
        $repository = $this->createStub(WhitelistRepositoryInterface::class);
        $repository->method('deleteById')->willReturn(true);

        (new Delete($this->context(['id' => '5']), $repository, $this->escapedMessages()))->execute();

        $this->assertSame([['success', 'The whitelist entry has been deleted.']], $this->messages);
    }

    public function testEditReportsAMissingEntry(): void
    {
        $repository = $this->createStub(WhitelistRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        (new Edit($this->context(['id' => '12']), $repository, $this->escapedMessages()))->execute();

        $this->assertSame([['error', 'Whitelist entry 12 does not exist.']], $this->messages);
    }

    public function testImportWithoutAFileSaysSo(): void
    {
        (new Import($this->context([]), $this->createStub(WhitelistImporter::class), $this->escapedMessages()))->execute();

        $this->assertSame([['error', 'Choose a file to import.']], $this->messages);
    }

    /**
     * Admin action context with the request parameters, recording the messages queued.
     *
     * @param array<string, string> $params
     * @return Context
     */
    private function context(array $params): Context
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $params[$key] ?? $default
        );
        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnSelf();
        $redirects = $this->createStub(RedirectFactory::class);
        $redirects->method('create')->willReturn($redirect);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirects);
        $context->method('getMessageManager')->willReturn($this->messageManager());

        return $context;
    }

    /**
     * @return EscapedMessages
     */
    private function escapedMessages(): EscapedMessages
    {
        return new EscapedMessages($this->messageManager(), new Escaper());
    }

    /**
     * Message manager that records each message with its type.
     *
     * @return ManagerInterface
     */
    private function messageManager(): ManagerInterface
    {
        $manager = $this->createStub(ManagerInterface::class);
        foreach (['success' => 'addSuccessMessage', 'error' => 'addErrorMessage', 'notice' => 'addNoticeMessage', 'warning' => 'addWarningMessage'] as $type => $method) {
            $manager->method($method)->willReturnCallback(function (string $message) use ($type, $manager): ManagerInterface {
                $this->messages[] = [$type, $message];

                return $manager;
            });
        }

        return $manager;
    }
}
