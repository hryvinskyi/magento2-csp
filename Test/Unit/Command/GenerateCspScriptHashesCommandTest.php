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
use Hryvinskyi\Csp\Model\ScriptHash\ScriptSourceInterface;
use Hryvinskyi\Csp\Model\ScriptHash\ScriptSourcePool;
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
        $scanner->method('scan')->willReturnCallback(static fn (int $storeId, array $types): array => [[
            'label' => 'CMS page "Home" (ID 2)',
            'scripts' => ['window.store = ' . $storeId . ';', 'window.other = 1;'],
            'skipped' => ['external script'],
            'warning' => null,
        ]]);
        $allowed = [];
        $whitelister = $this->createStub(ScriptHashWhitelister::class);
        $whitelister->method('hash')->willReturn('abc=');
        $whitelister->method('whitelist')->willReturnCallback(
            static function (string $script, int $storeId) use (&$allowed): string {
                $allowed[] = [$script, $storeId];

                return ScriptHashWhitelister::CREATED;
            }
        );
        $tester = $this->tester($scanner, $whitelister, ['page', 'block', 'config']);
        $tester->setInputs(['y', 'n', 'y', 'n']);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertSame([['window.store = 1;', 1], ['window.store = 2;', 2]], $allowed);
        $this->assertStringContainsString('skipped: external script', $tester->getDisplay());
    }

    public function testListsContentTypesWithoutBuildingTheScanner(): void
    {
        $scanner = $this->createMock(StoreContentScanner::class);
        $scanner->expects($this->never())->method($this->anything());

        $command = $this->command($scanner, $this->createStub(ScriptHashWhitelister::class), ['page', 'block']);

        $this->assertSame(
            'Content to scan (page, block); all by default',
            $command->getDefinition()->getOption('type')->getDescription()
        );
    }

    public function testScansEveryContentTypeByDefault(): void
    {
        $scanned = [];
        $scanner = $this->createStub(StoreContentScanner::class);
        $scanner->method('scan')->willReturnCallback(static function (int $storeId, array $types) use (&$scanned): array {
            $scanned[] = $types;

            return [];
        });

        $tester = $this->tester($scanner, $this->createStub(ScriptHashWhitelister::class), ['page', 'config']);

        $this->assertSame(Command::SUCCESS, $tester->execute(['--store' => '1']));
        $this->assertSame([['page', 'config']], $scanned);
    }

    public function testYesAllowsWithoutAsking(): void
    {
        $scanner = $this->createStub(StoreContentScanner::class);
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

    public function testPrintsLabelsAsTextNotAsConsoleTags(): void
    {
        $scanner = $this->createStub(StoreContentScanner::class);
        $scanner->method('scan')->willReturn([[
            'label' => 'CMS page "<error>Sale</error>" (ID 3)', 'scripts' => [], 'skipped' => [], 'warning' => 'rendering failed: <info>x</info>',
        ]]);
        $whitelister = $this->createStub(ScriptHashWhitelister::class);

        $tester = $this->tester($scanner, $whitelister);

        $this->assertSame(Command::SUCCESS, $tester->execute(['--store' => '1']));
        $this->assertStringContainsString('CMS page "<error>Sale</error>" (ID 3)', $tester->getDisplay());
        $this->assertStringContainsString('rendering failed: <info>x</info>', $tester->getDisplay());
    }

    /**
     * @param StoreContentScanner $scanner
     * @param ScriptHashWhitelister $whitelister
     * @param list<string> $sourceCodes
     * @return CommandTester
     */
    private function tester(
        StoreContentScanner $scanner,
        ScriptHashWhitelister $whitelister,
        array $sourceCodes = ['page']
    ): CommandTester {
        return new CommandTester($this->command($scanner, $whitelister, $sourceCodes));
    }

    /**
     * @param StoreContentScanner $scanner
     * @param ScriptHashWhitelister $whitelister
     * @param list<string> $sourceCodes
     * @return GenerateCspScriptHashesCommand
     */
    private function command(
        StoreContentScanner $scanner,
        ScriptHashWhitelister $whitelister,
        array $sourceCodes
    ): GenerateCspScriptHashesCommand {
        $sources = [];
        foreach ($sourceCodes as $code) {
            $sources[$code] = $this->createStub(ScriptSourceInterface::class);
        }
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
        $command = new GenerateCspScriptHashesCommand(
            $scanner,
            new ScriptSourcePool($sources),
            $whitelister,
            $storeManager,
            $questions
        );
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        return $command;
    }
}
