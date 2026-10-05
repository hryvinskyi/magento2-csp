<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

use Magento\Framework\App\CacheInterface;

/**
 * Application cache kept in memory.
 */
class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, string>
     */
    public array $records = [];

    /**
     * @var array<string, list<string>>
     */
    public array $tags = [];

    /**
     * @inheritDoc
     */
    public function getFrontend()
    {
        throw new \LogicException('No cache frontend in memory.');
    }

    /**
     * @param string $identifier
     * @return string|false
     */
    public function load($identifier)
    {
        return $this->records[(string)$identifier] ?? false;
    }

    /**
     * @param string $data
     * @param string $identifier
     * @param array<string> $tags
     * @param int|null $lifeTime
     * @return bool
     */
    public function save($data, $identifier, $tags = [], $lifeTime = null)
    {
        $this->records[(string)$identifier] = (string)$data;
        $this->tags[(string)$identifier] = array_values($tags);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove($identifier)
    {
        unset($this->records[(string)$identifier], $this->tags[(string)$identifier]);

        return true;
    }

    /**
     * @param array<string> $tags
     * @return bool
     */
    public function clean($tags = [])
    {
        $this->records = [];
        $this->tags = [];

        return true;
    }
}
