<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Former command that flushed the CSP policy cache, kept so deployment scripts that call it keep working.
 *
 * @deprecated 2.0.0 The policy cache was removed; Magento's block and page caches hold the policies now.
 */
class FlushCspCacheCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:csp:cache:flush')
            ->setDescription('Deprecated: does nothing. The CSP policy cache was removed in 2.0.0.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            '<comment>The CSP policy cache was removed in 2.0.0; there is nothing to flush. '
            . 'Remove this command from your deployment scripts.</comment>'
        );

        return Command::SUCCESS;
    }
}
