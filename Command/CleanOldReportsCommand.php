<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Hryvinskyi\Csp\Api\Config\ReportCleanupConfigInterface;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes old violation reports now, with the configured or the given mode and threshold.
 */
class CleanOldReportsCommand extends Command
{
    private const OPTION_MODE = 'mode';
    private const OPTION_THRESHOLD = 'threshold';
    private const OPTION_DRY_RUN = 'dry-run';

    /**
     * @param ReportCleanupConfigInterface $config
     * @param ReportCleanupInterface $reportCleanup
     */
    public function __construct(
        private readonly ReportCleanupConfigInterface $config,
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
            ->setDescription('Delete old CSP violation reports')
            ->addOption(
                self::OPTION_MODE,
                'm',
                InputOption::VALUE_REQUIRED,
                sprintf('Cleanup mode (%s); the configured mode by default', implode(', ', $this->reportCleanup->modes()))
            )
            ->addOption(
                self::OPTION_THRESHOLD,
                't',
                InputOption::VALUE_REQUIRED,
                'Days for the date mode, reports to keep for the count mode; the configured threshold by default'
            )
            ->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Only show how many reports would be deleted');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getOption(self::OPTION_MODE);
        $mode = is_string($mode) && $mode !== '' ? $mode : $this->config->getReportCleanupMode();
        $threshold = $input->getOption(self::OPTION_THRESHOLD);
        $threshold = is_numeric($threshold) ? (int)$threshold : $this->config->getReportCleanupThreshold();

        try {
            if ($input->getOption(self::OPTION_DRY_RUN)) {
                $output->writeln(sprintf(
                    '<comment>%d report(s) would be deleted (mode %s, threshold %d).</comment>',
                    $this->reportCleanup->countAffected($mode, $threshold),
                    $mode,
                    $threshold
                ));

                return Command::SUCCESS;
            }
            $output->writeln(sprintf(
                '<info>%d report(s) deleted (mode %s, threshold %d).</info>',
                $this->reportCleanup->clean($mode, $threshold),
                $mode,
                $threshold
            ));
        } catch (\InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
