<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Conversion;

/**
 * What converting a report group did.
 */
enum ConversionOutcome
{
    /** A new whitelist entry was created. */
    case Created;

    /** An entry with the same directive, value and area now also applies to the group's store. */
    case StoresExtended;

    /** An enabled entry with the same value already applies to the group's store and area. */
    case AlreadyAllowed;

    /** An enabled wildcard entry already covers the value in the group's store and area. */
    case CoveredByWildcard;

    /** An entry with the same directive, value and area exists but is disabled; nothing changed. */
    case ExistsDisabled;

    /** The value must not be whitelisted; nothing changed. */
    case Refused;

    /**
     * Whether the value is allowed for the group's store and area afterwards, so the group can be removed.
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        return in_array($this, [self::Created, self::StoresExtended, self::AlreadyAllowed, self::CoveredByWildcard], true);
    }
}
