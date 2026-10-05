<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Hryvinskyi\Csp\Model\ScriptHash\ScriptHashWhitelister;
use Hryvinskyi\Csp\Model\ScriptHash\StoreContentScanner;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestionFactory;

/**
 * Finds the inline scripts of rendered storefront content and allows each by its hash in the store view it renders in.
 */
class GenerateCspScriptHashesCommand extends Command
{
    private const OPTION_STORE = 'store';
    private const OPTION_TYPE = 'type';
    private const OPTION_YES = 'yes';
    private const OUTCOME_MESSAGES = [
        ScriptHashWhitelister::CREATED => '<info>allowed</info>',
        ScriptHashWhitelister::EXTENDED => '<info>allowed in this store too</info>',
        ScriptHashWhitelister::EXISTS => '<comment>already allowed</comment>',
    ];

    /**
     * @param StoreContentScanner $scanner
     * @param ScriptHashWhitelister $whitelister
     * @param StoreManagerInterface $storeManager
     * @param ConfirmationQuestionFactory $confirmationQuestionFactory
     */
    public function __construct(
        private readonly StoreContentScanner $scanner,
        private readonly ScriptHashWhitelister $whitelister,
        private readonly StoreManagerInterface $storeManager,
        private readonly ConfirmationQuestionFactory $confirmationQuestionFactory
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:csp:generate-script-hashes')
            ->setDescription('Allow the inline scripts of CMS pages, CMS blocks and configuration by their hashes')
            ->addOption(self::OPTION_STORE, 's', InputOption::VALUE_REQUIRED, 'Store view id; every store view by default')
            ->addOption(
                self::OPTION_TYPE,
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Content to scan (%s); all by default', implode(', ', $this->scanner->sourceCodes()))
            )
            ->addOption(self::OPTION_YES, 'y', InputOption::VALUE_NONE, 'Allow every script found without asking');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $types = array_values(array_filter((array)$input->getOption(self::OPTION_TYPE), 'is_string'));
        $types = $types === [] ? $this->scanner->sourceCodes() : $types;
        $allowAll = (bool)$input->getOption(self::OPTION_YES);
        $helper = $this->getHelper('question');
        $question = $helper instanceof QuestionHelper ? $helper : null;

        try {
            foreach ($this->storeIds($input->getOption(self::OPTION_STORE)) as $storeId) {
                $output->writeln(sprintf('<info>Store view %d</info>', $storeId));
                foreach ($this->scanner->scan($storeId, $types) as $entity) {
                    $this->processEntity($entity, $storeId, $allowAll, $question, $input, $output);
                }
            }
        } catch (\InvalidArgumentException | LocalizedException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Report one entity and allow its scripts after confirmation.
     *
     * @param array{label: string, scripts: list<string>, skipped: list<string>, warning: string|null} $entity
     * @param int $storeId
     * @param bool $allowAll
     * @param QuestionHelper|null $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws LocalizedException
     */
    private function processEntity(
        array $entity,
        int $storeId,
        bool $allowAll,
        ?QuestionHelper $question,
        InputInterface $input,
        OutputInterface $output
    ): void {
        if ($entity['scripts'] === [] && $entity['warning'] === null) {
            return;
        }
        $output->writeln(sprintf('  %s', $entity['label']));
        if ($entity['warning'] !== null) {
            $output->writeln(sprintf('    <comment>%s</comment>', $entity['warning']));
        }
        foreach ($entity['skipped'] as $reason) {
            $output->writeln(sprintf('    skipped: %s', $reason));
        }
        foreach ($entity['scripts'] as $script) {
            $output->writeln(sprintf('    sha256-%s', $this->whitelister->hash($script)));
            if ($output->isVerbose()) {
                $output->writeln($script, OutputInterface::OUTPUT_RAW);
            }
            $confirmed = $allowAll || ($question !== null && $question->ask(
                $input,
                $output,
                $this->confirmationQuestionFactory->create(['question' => '    Allow this script? [y/N] ', 'default' => false])
            ));
            if ($confirmed) {
                $output->writeln('    ' . self::OUTCOME_MESSAGES[$this->whitelister->whitelist($script, $storeId)]);
            }
        }
    }

    /**
     * Store view ids to scan.
     *
     * @param mixed $option
     * @return list<int>
     * @throws LocalizedException
     */
    private function storeIds(mixed $option): array
    {
        if (is_numeric($option)) {
            return [(int)$this->storeManager->getStore((int)$option)->getId()];
        }

        return array_values(array_map(
            static fn (\Magento\Store\Api\Data\StoreInterface $store): int => (int)$store->getId(),
            $this->storeManager->getStores()
        ));
    }
}
