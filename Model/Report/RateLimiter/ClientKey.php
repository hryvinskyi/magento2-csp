<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\RateLimiter;

/**
 * Key a client is rate limited under: its IPv4 address, or the /64 network of its IPv6 address.
 *
 * A single IPv6 subscriber is usually given a whole /64, so keying each address would hand one client billions of
 * allowances. An IPv4 address mapped into IPv6 counts as that IPv4 address; anything that is not an IP address is
 * kept as it is.
 */
class ClientKey
{
    private const IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /**
     * Rate-limit key of the client address.
     *
     * @param string $address
     * @return string
     */
    public function of(string $address): string
    {
        $packed = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? inet_pton($address) : false;
        if ($packed === false || strlen($packed) !== 16) {
            return $address;
        }
        if (str_starts_with($packed, self::IPV4_MAPPED_PREFIX)) {
            $ipv4 = inet_ntop(substr($packed, 12));

            return $ipv4 === false ? $address : $ipv4;
        }
        $network = inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8));

        return $network === false ? $address : $network . '/64';
    }
}
