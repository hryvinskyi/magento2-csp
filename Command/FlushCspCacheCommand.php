<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Command;

use Hryvinskyi\Csp\Model\Cache\Type\CspPolicies;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to flush CSP policies cache
 */
class FlushCspCacheCommand extends Command
{
    public function __construct(
        private readonly CspPolicies $cspPoliciesCache
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:csp:cache:flush')
            ->setDescription('Flush CSP policies cache');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Flushing CSP policies cache...</info>');
        
        try {
            $this->cspPoliciesCache->clean();
            $output->writeln('<info>CSP policies cache has been flushed successfully.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error flushing CSP policies cache: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}