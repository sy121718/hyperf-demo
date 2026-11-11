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
use App\Dto\Config\ConfigRequest;
use App\Model\System\SysConfigModel;
use Psr\SimpleCache\CacheInterface;

class Update
{
    public function __construct(
        protected SysConfigModel $configModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(ConfigRequest $request): array
    {
        $id = (int) $request->id;
        $config = $this->configModel->findOrNotFound($id, '配置');

        if ($request->group_key !== null && $request->group_key !== $config->group_key) {
            $this->configModel->where('group_key', $request->group_key)
                ->where('id', '!=', $id)->exists()
                && paramError('配置分组键已存在');
        }

        // 更新后 group_key 可能已变成新值，旧 key 的缓存必须用改前的值清
        $oldGroupKey = $config->group_key;

        $config->update($request->toDbArrayFiltered());

        $this->cache->delete(CacheKeys::config($oldGroupKey));
        $config->refresh();
        if ($config->group_key !== $oldGroupKey) {
            $this->cache->delete(CacheKeys::config($config->group_key));
        }

        return $config->toArray();
    }
}
