<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Service\Config;

use App\Common\CacheKeys;
use App\Model\System\SysConfigModel;
use Psr\SimpleCache\CacheInterface;

class Delete
{
    public function __construct(
        protected SysConfigModel $configModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(int $id): bool
    {
        $config = $this->configModel->findOrNotFound($id, '配置');

        $groupKey = $config->group_key;
        $config->delete();
        $this->cache->delete(CacheKeys::config($groupKey));

        return true;
    }
}
