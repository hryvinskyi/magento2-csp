<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCspScriptHashesCommand extends Command
{
    private const SCRIPT_PATTERN = '/<script[^>]*>(.*?)<\/script>/is';
    private const EXCLUDE_SCRIPT_TYPES = [
        'application/ld+json',
        'application/json',
        'text/template',
        'text/ng-template',
        'text/x-tmpl',
        'text/x-custom-template',
        'text/x-magento-init',
        'text/x-magento-template',
    ];
    private const POLICY_TYPE = 'script-src';
    private const HASH_ALGORITHM = 'sha256';
    private const VALUE_TYPE = 'hash';

    private $io;
    private array $processedBlocks = [];
    private array $processedPages = [];
    private array $processedConfig = [];

    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly CspHashGeneratorInterface $cspHashGenerator,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly WhitelistInterfaceFactory $whitelistFactory,
        private readonly State $appState,
        private readonly TypeListInterface $cacheTypeList,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resourceConnection,
        private readonly FilterProvider $filterProvider,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:csp:generate-script-hashes')
            ->setDescription('Scan CMS entities for inline scripts and add them to CSP whitelist' .
                ' When processing type "config", configuration data for the default scope will also be included.'
            )
            ->addOption(
                'store',
                's',
                InputOption::VALUE_OPTIONAL,
                'If you want to specify a store ID, use this option. Default is all stores.',
                0
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Type of entities to process: "page", "block", or "config". Can specify multiple times.',
                []
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->displayBanner($output);
        $this->setAreaCode();
        $storeId = (int)$input->getOption('store');
        $stores = $this->getStores($storeId);
        $types = $input->getOption('type');

        // If no types specified, process all types
        $processAll = empty($types);
        $processPages = $processAll || in_array('page', $types);
        $processBlocks = $processAll || in_array('block', $types);
        $processConfig = $processAll || in_array('config', $types);

        // Validate types
        $validTypes = ['page', 'block', 'config'];
        foreach ($types as $type) {
            if (!in_array($type, $validTypes)) {
                $output->writeln("<error>Invalid type: '$type'. Valid types are: " . implode(', ', $validTypes) . "</error>");
                return Command::FAILURE;
            }
        }

        $totalScripts = 0;
        $totalAdded = 0;

        foreach ($stores as $store) {
            $currentStoreId = (int)$store->getId();
            $this->displayStoreHeader($output, $currentStoreId, $store->getName());

            $storeFound = 0;
            $storeAdded = 0;

            if ($processPages) {
                $result = $this->processCmsPages($input, $output, $currentStoreId);
                $totalScripts += $result['found'];
                $totalAdded += $result['added'];
                $storeFound += $result['found'];
                $storeAdded += $result['added'];
            }

            if ($processBlocks) {
                $result = $this->processCmsBlocks($input, $output, $currentStoreId);
                $totalScripts += $result['found'];
                $totalAdded += $result['added'];
                $storeFound += $result['found'];
                $storeAdded += $result['added'];
            }

            if ($processConfig) {
                $result = $this->processConfigData($input, $output, $currentStoreId);
                $totalScripts += $result['found'];
                $totalAdded += $result['added'];
                $storeFound += $result['found'];
                $storeAdded += $result['added'];
            }

            $this->displayStoreSummary($output, $currentStoreId, $storeFound, $storeAdded);
        }

        $this->clearCaches();
        $this->displayFinalSummary($output, $totalScripts, $totalAdded);

        return Command::SUCCESS;
    }

    /**
     * Display command banner
     *
     * @param OutputInterface $output
     * @return void
     */
    private function displayBanner(OutputInterface $output): void
    {
        $this->io->block(
            'CSP SCRIPT HASH GENERATOR' . PHP_EOL . 'Scan CMS entities for inline scripts and add to CSP whitelist',
            null,
            'bg=green;fg=white',
            '  ',
            true
        );
    }

    /**
     * Display store header
     *
     * @param OutputInterface $output
     * @param int $storeId
     * @param string $storeName
     * @return void
     */
    private function displayStoreHeader(OutputInterface $output, int $storeId, string $storeName): void
    {
        $this->io->block(
            "PROCESSING STORE $storeName (ID: $storeId)",
            null,
            'bg=cyan;fg=white',
            '  ',
            true
        );
    }

    /**
     * Display store summary
     *
     * @param OutputInterface $output
     * @param int $storeId
     * @param int $found
     * @param int $added
     * @return void
     */
    private function displayStoreSummary(OutputInterface $output, int $storeId, int $found, int $added): void
    {
        $this->io->block(
            "STORE ID $storeId PROCESSING COMPLETE" . PHP_EOL .
            " Scripts found: $found" . PHP_EOL .
            " Scripts added: $added",
            null,
            'bg=green;fg=black',
            '  ',
            true
        );
    }

    /**
     * Display final summary
     *
     * @param OutputInterface $output
     * @param int $totalScripts
     * @param int $totalAdded
     * @return void
     */
    private function displayFinalSummary(OutputInterface $output, int $totalScripts, int $totalAdded): void
    {
        $this->io->block(
            "FINAL SUMMARY" . PHP_EOL .
            "   Total scripts found: $totalScripts" . PHP_EOL .
            "   Total scripts added: $totalAdded" . PHP_EOL .
            "   All caches cleaned successfully",
            null,
            'bg=magenta;fg=white',
            '  ',
            true
        );
    }

    /**
     * Display section header
     *
     * @param OutputInterface $output
     * @param string $title
     * @return void
     */
    private function displaySectionHeader(OutputInterface $output, string $title): void
    {
        $this->io->block(
            "  SCANNING $title",
            null,
            'bg=blue;fg=white',
            '  ',
            true
        );
    }

    /**
     * Set area code for frontend
     *
     * @return void
     */
    private function setAreaCode(): void
    {
        try {
            $this->appState->setAreaCode(Area::AREA_FRONTEND);
        } catch (\Exception $e) {
            // Area is already set
        }
    }

    /**
     * Get store collection based on input store ID
     *
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    private function getStores(int $storeId): array
    {
        if ($storeId === 0) {
            return $this->storeManager->getStores();
        }

        return [$this->storeManager->getStore($storeId)];
    }

    /**
     * Process CMS pages for given store ID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function processCmsPages(InputInterface $input, OutputInterface $output, int $storeId): array
    {
        $this->displaySectionHeader($output, 'CMS PAGES');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('store_id', [0, $storeId], 'IN')
            ->create();
        $pages = $this->pageRepository->getList($searchCriteria);

        $stats = ['found' => 0, 'added' => 0];
        $count = $pages->getTotalCount();

        if ($count === 0) {
            $output->writeln('<comment>No CMS pages found for this store.</comment>');
            return $stats;
        }

        $output->writeln("<info>Found $count CMS pages to process.</info>");

        $index = 0;
        foreach ($pages->getItems() as $page) {
            $progress = $index + 1;
            $output->writeln("");
            $output->writeln("");
            $output->writeln(
                "<fg=blue>┌─ Processing page {$progress}/{$count}: </><fg=green>{$page->getTitle()}</> <fg=yellow>(ID: {$page->getId()})</>"
            );

            if (isset($this->processedPages[$page->getId()])) {
                $output->writeln("<fg=blue>├─</> <comment> Already processed this page.</comment>");
                $result = ['found' => 0, 'added' => 0];
            } else {
                $content = $this->getFilteredContent(
                    $page->getContent(),
                    $output,
                    'page',
                    $this->filterProvider->getPageFilter()
                );
                $result = $this->extractAndProcessScripts($content, $input, $output, $storeId);
            }

            $stats['found'] += $result['found'];
            $stats['added'] += $result['added'];
            $this->processedPages[$page->getId()] = true;

            $output->writeln(
                "<fg=blue>└─ Page processing complete: {$result['found']} scripts found, {$result['added']} added</>"
            );
            $index++;
        }

        return $stats;
    }

    /**
     * Process CMS blocks for given store ID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function processCmsBlocks(InputInterface $input, OutputInterface $output, int $storeId): array
    {
        $this->displaySectionHeader($output, 'CMS BLOCKS');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('store_id', [0, $storeId], 'IN')
            ->create();
        $blocks = $this->blockRepository->getList($searchCriteria);

        $stats = ['found' => 0, 'added' => 0];
        $count = $blocks->getTotalCount();

        if ($count === 0) {
            $output->writeln('<comment>No CMS blocks found for this store.</comment>');
            return $stats;
        }

        $output->writeln("<info>Found $count CMS blocks to process.</info>");
        $index = 0;
        foreach ($blocks->getItems() as $block) {
            $progress = $index + 1;

            $output->writeln("");
            $output->writeln("");
            $output->writeln(
                "<fg=blue>┌─ Processing block {$progress}/{$count}: </><fg=green>{$block->getTitle()}</> <fg=yellow>(ID: {$block->getId()})</>"
            );

            if (isset($this->processedBlocks[$block->getId()])) {
                $output->writeln("<fg=blue>├─</> <comment> Already processed this block.</comment>");
                $result = ['found' => 0, 'added' => 0];
            } else {
                $content = $this->getFilteredContent(
                    $block->getContent(),
                    $output,
                    'block',
                    $this->filterProvider->getBlockFilter()
                );

                $result = $this->extractAndProcessScripts($content, $input, $output, $storeId);
            }

            $stats['found'] += $result['found'];
            $stats['added'] += $result['added'];

            $this->processedBlocks[$block->getId()] = true;

            $output->writeln(
                "<fg=blue>└─ Block processing complete: {$result['found']} scripts found, {$result['added']} added</>"
            );
            $index++;
        }

        return $stats;
    }

    /**
     * Process core config data for given store ID
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function processConfigData(InputInterface $input, OutputInterface $output, int $storeId): array
    {
        $this->displaySectionHeader($output, 'CORE CONFIG DATA');
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');
        $select = $connection->select()->from($configTable)
            ->where('value IS NOT NULL AND value != "" AND (
                    (
                        scope = \'default\' AND
                        scope_id = 0
                    ) OR
                    (
                        (
                            scope = \'website\' OR
                            scope = \'websites\'
                        ) AND
                        scope_id = :scope_id_website
                    ) OR
                    (
                        (
                            scope = \'store\' OR
                            scope = \'stores\'
                        ) AND
                        scope_id = :scope_id_store
                    )
                )');

        $allConfigs = $connection->fetchAll($select, ['scope_id_website' => $websiteId, 'scope_id_store' => $storeId]);

        $stats = ['found' => 0, 'added' => 0];
        $count = count($allConfigs);

        if ($count === 0) {
            $output->writeln('<comment>No configuration data found.</comment>');
            return $stats;
        }

        $output->writeln("Found $count configuration entries to process.</info>");
        $processedCount = 0;

        foreach ($allConfigs as $config) {
            $content = $config['value'] ?? '';

            $processedCount++;
            $output->writeln("");
            $output->writeln("");
            $output->writeln(
                sprintf(
                    "<fg=blue>┌─ Processing config %d/%d: </><fg=green>%s</> <fg=yellow>(scope: %s, scope_id: %s)</> ",
                    $processedCount,
                    $count,
                    $config['path'],
                    $config['scope'],
                    $config['scope_id']
                )
            );

            if (isset($this->processedConfig[$config['config_id']])) {
                $output->writeln("<fg=blue>├─</> <comment> Already processed this config entry.</comment>");
                $result = ['found' => 0, 'added' => 0];
            } else {
                $result = $this->extractAndProcessScripts((string)$content, $input, $output, $storeId);
            }

            $stats['found'] += $result['found'];
            $stats['added'] += $result['added'];

            $output->writeln(
                "<fg=blue>└─ Config processing complete: {$result['found']} scripts found, {$result['added']} added</>"
            );

            $this->processedConfig[$config['config_id']] = true;
        }

        return $stats;
    }

    /**
     * Get filtered content using appropriate filter
     *
     * @param string $content
     * @param OutputInterface $output
     * @param string $type
     * @param mixed $filter
     * @return string
     */
    private function getFilteredContent(string $content, OutputInterface $output, string $type, $filter): string
    {
        try {
//            $filteredContent = $filter->filter($content);
            $filteredContent = $content;

            if (!str_contains($filteredContent, 'Error filtering template:')) {
                return $filteredContent;
            }

            $output->writeln("<fg=blue>├─</> <fg=red>Failed to filter {$type} content: {$filteredContent}</fg=red>");
            $output->writeln("<fg=blue>├─</> <fg=yellow>Using raw content instead.</fg=yellow>");
            return $content;
        } catch (\Throwable $e) {
            $output->writeln("<fg=blue>├─</> <fg=red>Failed to filter {$type} content: {$e->getMessage()}</fg=red>");
            $output->writeln("<fg=blue>├─</> <fg=yellow>Using raw content instead.</fg=yellow>");
            return $content;
        }
    }

    /**
     * Extract and process scripts from content
     *
     * @param string $content
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param int $storeId
     * @return array Returns stats with 'found' and 'added' counts
     */
    private function extractAndProcessScripts(
        string $content,
        InputInterface $input,
        OutputInterface $output,
        int $storeId
    ): array {
        $helper = new QuestionHelper();
        preg_match_all(self::SCRIPT_PATTERN, $content, $matches, PREG_SET_ORDER);

        $stats = [
            'found' => 0,
            'added' => 0
        ];

        if (empty($matches)) {
            $output->writeln('<fg=blue>├─</> <comment>No script tags found in content</comment>');
            return $stats;
        }

        $output->writeln("<fg=blue>├─</> <info>Found " . count($matches) . " script tags total</info>");
        
        $scriptIndex = 0;
        $skippedIndex = 0;
        $totalIndex = 0;

        foreach ($matches as $match) {
            $totalIndex++;
            $fullTag = $match[0];
            $scriptContent = $match[1];

            $output->writeln("<fg=blue>├─</> <fg=yellow>--- Examining Script Tag #$totalIndex ---</fg=yellow>");
            $output->writeln("<fg=blue>├─</> <comment>Content length: " . strlen($scriptContent) . " characters</comment>");
            
            // Check if we should skip this script
            if ($this->shouldSkipScript($fullTag, $scriptContent)) {
                $skippedIndex++;
                $skipReason = $this->getSkipReason($fullTag, $scriptContent);
                $output->writeln("<fg=blue>├─</> <comment>Skipping script #$skippedIndex: $skipReason</comment>");
                continue;
            }

            $scriptIndex++;
            $stats['found']++;

            $output->writeln("");
            $output->writeln("<fg=blue>├─</> <fg=cyan>═══ Processing Script #$scriptIndex ═══</fg=cyan>");

            $hash = $this->cspHashGenerator->execute($scriptContent);
            $this->displayScriptInfo($scriptContent, $hash, $output);

            // Check if script already exists in whitelist
            if ($this->handleExistingWhitelist($hash, $storeId, $output)) {
                $output->writeln("<fg=blue>├─</> <comment>Script already exists in whitelist</comment>");
                continue;
            }

            // Ask for confirmation to add new script
            $question = new ConfirmationQuestion(
                '<fg=blue>├─</> <question>Add this script to CSP whitelist? (y/n) </question>',
                false
            );
            if ($helper->ask($input, $output, $question)) {
                $this->addToWhitelist($scriptContent, $hash, $storeId, $output);
                $stats['added']++;
                $output->writeln("<fg=blue>├─</> <info>Script #$scriptIndex added to whitelist</info>");
            } else {
                $output->writeln("<fg=blue>├─</> <comment>Script #$scriptIndex skipped by user</comment>");
            }
        }

        $totalProcessed = $scriptIndex + $skippedIndex;
        $output->writeln("");
        $output->writeln("<fg=blue>├─</> <info>Script processing summary:</info>");
        $output->writeln("<fg=blue>├─</> <info>  - Total scripts found: $totalProcessed</info>");
        $output->writeln("<fg=blue>├─</> <info>  - Inline scripts processed: $scriptIndex</info>");
        $output->writeln("<fg=blue>├─</> <info>  - Scripts skipped: $skippedIndex</info>");
        $output->writeln("<fg=blue>├─</> <info>  - Scripts added to whitelist: {$stats['added']}</info>");

        return $stats;
    }

    /**
     * Check if script should be skipped
     *
     * @param string $fullTag
     * @param string $scriptContent
     * @return bool
     */
    private function shouldSkipScript(string $fullTag, string $scriptContent): bool
    {
        // Skip empty scripts
        if (trim($scriptContent) === '') {
            return true;
        }

        // Skip external scripts - check for src attribute in opening script tag only
        if ($this->hasSourceAttribute($fullTag)) {
            return true;
        }

        // Skip excluded script types
        foreach (self::EXCLUDE_SCRIPT_TYPES as $excludeType) {
            if (stripos($fullTag, 'type="' . $excludeType . '"') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get reason why script should be skipped
     *
     * @param string $fullTag
     * @param string $scriptContent
     * @return string
     */
    private function getSkipReason(string $fullTag, string $scriptContent): string
    {
        // Check empty scripts
        if (trim($scriptContent) === '') {
            return 'Empty script content';
        }

        // Check external scripts
        if ($this->hasSourceAttribute($fullTag)) {
            // Extract src attribute for better reporting
            if (preg_match('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $fullTag, $matches)) {
                return 'External script (src=' . $matches[1] . ')';
            }
            return 'External script (has src attribute)';
        }

        // Check excluded script types
        foreach (self::EXCLUDE_SCRIPT_TYPES as $excludeType) {
            if (stripos($fullTag, 'type="' . $excludeType . '"') !== false) {
                return 'Excluded script type (' . $excludeType . ')';
            }
        }

        return 'Unknown reason';
    }

    /**
     * Check if script tag has a src attribute (external script)
     * Only checks the opening script tag, not content inside
     *
     * @param string $fullTag
     * @return bool
     */
    private function hasSourceAttribute(string $fullTag): bool
    {
        // Use regex to check for src attribute in the opening script tag only
        // This pattern matches <script...src=...> but not src= inside the script content
        return (bool) preg_match('/<script[^>]+src\s*=/i', $fullTag);
    }

    /**
     * Display script information with enhanced formatting
     *
     * @param string $scriptContent
     * @param string $hash
     * @param OutputInterface $output
     * @return void
     */
    private function displayScriptInfo(string $scriptContent, string $hash, OutputInterface $output): void
    {
        // Format the script content for better readability
        $output->writeln("<fg=blue>│</> ");

        $formattedScript = $this->formatScriptContent($scriptContent);
        $output->writeln("$formattedScript");

        $output->writeln("<fg=blue>│</> ");
        $output->writeln("<fg=blue>│</> <bg=green;fg=black>    HASH INFORMATION    </>");
        $output->writeln("<fg=blue>├─</> <comment> Algorithm:</comment> <info>" . self::HASH_ALGORITHM . "</info>");
        $output->writeln("<fg=blue>├─</> <comment> Value:</comment> <info>" . $hash . "</info>");
    }

    /**
     * Format script content for better readability
     *
     * @param string $scriptContent
     * @return string
     */
    private function formatScriptContent(string $scriptContent): string
    {
        // Trim whitespace
        $script = trim($scriptContent);

        // Add line numbers and indentation
        $lines = explode("\n", $script);
        $formattedLines = [];

        $maxLineNumWidth = strlen((string)count($lines));

        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;
            $paddedLineNum = str_pad((string)$lineNum, $maxLineNumWidth, ' ', STR_PAD_LEFT);
            $formattedLines[] = "<fg=blue>│</> <fg=cyan>$paddedLineNum</> │ " . $line;
        }

        return implode("\n", $formattedLines);
    }

    /**
     * Handle existing whitelist entry
     *
     * @param string $hash
     * @param int $storeId
     * @param OutputInterface $output
     * @return bool
     */
    private function handleExistingWhitelist(string $hash, int $storeId, OutputInterface $output): bool
    {
        $existingEntries = $this->whitelistRepository->getWhitelistByParams(
            self::POLICY_TYPE,
            self::VALUE_TYPE,
            $hash,
            self::HASH_ALGORITHM
        );

        if ($existingEntries->getTotalCount() === 0) {
            return false;
        }

        // Check if the store ID is already in the whitelist entry
        $existingEntry = current($existingEntries->getItems());
        $existingStoreIds = explode(',', $existingEntry->getStoreIds());

        if (in_array((string)$storeId, $existingStoreIds)) {
            $output->writeln('<fg=blue>├─</>  <bg=yellow;fg=black> NOTICE </> Whitelist entry already exists for this store.');
            return true;
        }

        // Add Store ID to existing entry
        $existingStoreIds[] = (string)$storeId;
        $existingEntry->setStoreIds(implode(',', $existingStoreIds));

        try {
            $this->whitelistRepository->save($existingEntry);
            $output->writeln('<fg=blue>├─</>  <bg=green;fg=black> UPDATED </> Store ID added to existing whitelist entry.');
        } catch (\Exception $e) {
            $output->writeln('<fg=blue>├─</>  <bg=red;fg=white> ERROR </> Failed to update whitelist: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Add script to whitelist
     *
     * @param string $script
     * @param string $hash
     * @param int $storeId
     * @param OutputInterface $output
     * @return void
     */
    private function addToWhitelist(string $script, string $hash, int $storeId, OutputInterface $output): void
    {
        try {
            // Check if whitelist entry already exists
            if ($this->handleExistingWhitelist($hash, $storeId, $output)) {
                return;
            }

            // Create new whitelist entry
            $whitelist = $this->whitelistFactory->create();
            $whitelist->setIdentifier($hash);
            $whitelist->setPolicy(self::POLICY_TYPE);
            $whitelist->setValueType(self::VALUE_TYPE);
            $whitelist->setValue($hash);
            $whitelist->setValueAlgorithm(self::HASH_ALGORITHM);
            $whitelist->setStatus(1);
            $whitelist->setStoreIds((string)$storeId);
            $whitelist->setScriptContent($script);

            $this->whitelistRepository->save($whitelist);

            $output->writeln('<fg=blue>├─</> <info> Added to whitelist successfully.</info>');
        } catch (\Exception $e) {
            $output->writeln('<fg=blue>├─</> <error> Failed to add to whitelist: ' . $e->getMessage() . '</error>');
        }
    }

    /**
     * Clear relevant caches
     *
     * @return void
     */
    private function clearCaches(): void
    {
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType('full_page');
        $this->cacheTypeList->cleanType('collections');
    }
}