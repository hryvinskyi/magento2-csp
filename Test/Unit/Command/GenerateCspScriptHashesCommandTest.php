<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Command;

use Hryvinskyi\Csp\Command\GenerateCspScriptHashesCommand;
use Hryvinskyi\Csp\Model\ScriptHash\ScriptHashWhitelister;
use Hryvinskyi\Csp\Model\ScriptHash\StoreContentScanner;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestionFactory;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateCspScriptHashesCommandTest extends TestCase
{
    public function testAllowsConfirmedScriptsOfEveryStore(): void
    {
        $scanner = $this->createStub(StoreContentScanner::class);
        $scanner->method('sourceCodes')->willReturn(['page', 'block', 'config']);
        $scanner->method('scan')->willReturnCallback(static fn (int $storeId, array $types): array => [[
            'label' => 'CMS page "Home" (ID 2)',
            'scripts' => ['window.store = ' . $storeId . ';', 'window.other = 1;'],
            'skipped' => ['external script'],
            'warning' => null,
        ]]);
        $whitelister = $this->createMock(ScriptHashWhitelister::class);
        $whitelister->method('hash')->willReturn('abc=');
        $whitelister->expects($this->exactly(2))
            ->method('whitelist')
            ->withConsecutive(['window.store = 1;', 1], ['window.store = 2;', 2])
            ->willReturn(ScriptHashWhitelister::CREATED);
        $tester = $this->tester($scanner, $whitelister);
        $tester->setInputs(['y', 'n', 'y', 'n']);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('skipped: external script', $tester->getDisplay());
    }

    public function testYesAllowsWithoutAsking(): void
    {
        $scanner = $this->createStub(StoreContentScanner::class);
        $scanner->method('sourceCodes')->willReturn(['page']);
        $scanner->method('scan')->willReturn([[
            'label' => 'CMS block "Footer" (ID 9)', 'scripts' => ['window.a = 1;'], 'skipped' => [], 'warning' => null,
        ]]);
        $whitelister = $this->createMock(ScriptHashWhitelister::class);
        $whitelister->method('hash')->willReturn('abc=');
        $whitelister->expects($this->once())->method('whitelist')->with('window.a = 1;', 1)->willReturn(ScriptHashWhitelister::EXISTS);

        $tester = $this->tester($scanner, $whitelister);

        $this->assertSame(Command::SUCCESS, $tester->execute(['--store' => '1', '--yes' => true]));
        $this->assertStringContainsString('already allowed', $tester->getDisplay());
    }

    /**
     * @param StoreContentScanner $scanner
     * @param ScriptHashWhitelister $whitelister
     * @return CommandTester
     */
    private function tester(StoreContentScanner $scanner, ScriptHashWhitelister $whitelister): CommandTester
    {
        $stores = [];
        foreach ([1, 2] as $id) {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);
            $stores[$id] = $store;
        }
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);
        $storeManager->method('getStore')->willReturnCallback(static fn (int $id): StoreInterface => $stores[$id]);
        $questions = $this->createStub(ConfirmationQuestionFactory::class);
        $questions->method('create')->willReturnCallback(
            static fn (array $data): ConfirmationQuestion => new ConfirmationQuestion(is_string($data['question'] ?? null) ? $data['question'] : '', false)
        );
        $command = new GenerateCspScriptHashesCommand($scanner, $whitelister, $storeManager, $questions);
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        return new CommandTester($command);
    }
}
