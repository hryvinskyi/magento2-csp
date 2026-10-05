<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Conversion;

use Magento\Framework\Message\ManagerInterface;

/**
 * Tells the admin what converting report groups did.
 */
class ConversionMessages
{
    /**
     * Add one message per outcome, and the reason of every refusal.
     *
     * @param ManagerInterface $messageManager
     * @param list<ConversionResult> $results
     * @return void
     */
    public function add(ManagerInterface $messageManager, array $results): void
    {
        $counts = [];
        foreach ($results as $result) {
            $counts[$result->outcome->name] = ($counts[$result->outcome->name] ?? 0) + 1;
            if ($result->refusal !== null) {
                $messageManager->addErrorMessage((string)$result->refusal);
            }
        }
        $created = $counts[ConversionOutcome::Created->name] ?? 0;
        $extended = $counts[ConversionOutcome::StoresExtended->name] ?? 0;
        $allowed = ($counts[ConversionOutcome::AlreadyAllowed->name] ?? 0)
            + ($counts[ConversionOutcome::CoveredByWildcard->name] ?? 0);
        $disabled = $counts[ConversionOutcome::ExistsDisabled->name] ?? 0;
        if ($created > 0) {
            $messageManager->addSuccessMessage((string)__('%1 whitelist entry(ies) created.', $created));
        }
        if ($extended > 0) {
            $messageManager->addSuccessMessage((string)__('%1 existing whitelist entry(ies) now also apply to the reported store.', $extended));
        }
        if ($allowed > 0) {
            $messageManager->addNoticeMessage((string)__('%1 report group(s) were already allowed by an enabled entry and have been removed.', $allowed));
        }
        if ($disabled > 0) {
            $messageManager->addWarningMessage((string)__('%1 report group(s) match a disabled whitelist entry. Enable the entry to allow them.', $disabled));
        }
    }
}
