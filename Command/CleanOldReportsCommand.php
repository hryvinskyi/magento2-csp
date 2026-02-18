<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Model\Config\Source\CleanupMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to clean old CSP violation reports
 */
class CleanOldReportsCommand extends Command
{
    private const OPTION_MODE = 'mode';
    private const OPTION_THRESHOLD = 'threshold';
    private const OPTION_DRY_RUN = 'dry-run';

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ReportCleanupInterface $reportCleanup
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:csp:report:clean')
            ->setDescription('Clean old CSP violation reports')
            ->addOption(
                self::OPTION_MODE,
                'm',
                InputOption::VALUE_OPTIONAL,
                'Cleanup mode: "date" (delete by age) or "count" (keep N newest records). Defaults to config value.'
            )
            ->addOption(
                self::OPTION_THRESHOLD,
                't',
                InputOption::VALUE_OPTIONAL,
                'Threshold value: days for date mode, max records for count mode. Defaults to config value.'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Show how many records would be deleted without actually deleting them.'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getOption(self::OPTION_MODE) ?? $this->config->getReportCleanupMode();
        $threshold = $input->getOption(self::OPTION_THRESHOLD)
            ? (int)$input->getOption(self::OPTION_THRESHOLD)
            : $this->config->getReportCleanupThreshold();
        $dryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);

        if (!in_array($mode, [CleanupMode::MODE_DATE, CleanupMode::MODE_COUNT], true)) {
            $output->writeln(sprintf('<error>Invalid mode "%s". Use "date" or "count".</error>', $mode));
            return Command::FAILURE;
        }

        if ($threshold <= 0) {
            $output->writeln('<error>Threshold must be a positive integer.</error>');
            return Command::FAILURE;
        }

        $totalCount = $this->reportCleanup->getTotalCount();
        $output->writeln(sprintf('<info>Total violation reports: %d</info>', $totalCount));

        if ($dryRun) {
            return $this->executeDryRun($output, $mode, $threshold);
        }

        return $this->executeCleanup($output, $mode, $threshold);
    }

    /**
     * Execute dry-run showing how many records would be deleted
     *
     * @param OutputInterface $output
     * @param string $mode
     * @param int $threshold
     *
     * @return int
     */
    private function executeDryRun(OutputInterface $output, string $mode, int $threshold): int
    {
        $wouldDelete = match ($mode) {
            CleanupMode::MODE_COUNT => $this->reportCleanup->countByCount($threshold),
            default => $this->reportCleanup->countByDate($threshold),
        };

        $modeLabel = $mode === CleanupMode::MODE_DATE
            ? sprintf('older than %d days', $threshold)
            : sprintf('exceeding %d records limit', $threshold);

        $output->writeln(sprintf(
            '<comment>[DRY RUN] Would delete %d records (%s).</comment>',
            $wouldDelete,
            $modeLabel
        ));

        return Command::SUCCESS;
    }

    /**
     * Execute actual cleanup
     *
     * @param OutputInterface $output
     * @param string $mode
     * @param int $threshold
     *
     * @return int
     */
    private function executeCleanup(OutputInterface $output, string $mode, int $threshold): int
    {
        try {
            $deleted = match ($mode) {
                CleanupMode::MODE_COUNT => $this->reportCleanup->cleanByCount($threshold),
                default => $this->reportCleanup->cleanByDate($threshold),
            };

            $modeLabel = $mode === CleanupMode::MODE_DATE
                ? sprintf('older than %d days', $threshold)
                : sprintf('keeping %d newest records', $threshold);

            $output->writeln(sprintf(
                '<info>Deleted %d records (%s).</info>',
                $deleted,
                $modeLabel
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Cleanup failed: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
