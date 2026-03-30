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
use App\Model\Config\SysConfigModel;
use Psr\SimpleCache\CacheInterface;

class Update
{
    public function __construct(
        protected SysConfigModel $configModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(int $id, array $data): array
    {
        $id > 0 || paramError('配置ID不能为空');

        $config = $this->configModel->find($id);
        ! $config && paramError('配置不存在', 404);

        if (! empty($data['group_key']) && $data['group_key'] !== $config->group_key) {
            $this->configModel->where('group_key', $data['group_key'])
                ->where('id', '!=', $id)->exists()
                && paramError('配置分组键已存在');
        }

        $updateData = array_filter([
            'group_key' => $data['group_key'] ?? null,
            'group_name' => $data['group_name'] ?? null,
            'config_data' => $data['config_data'] ?? null,
            'remark' => $data['remark'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($v) => $v !== null);

        $config->update($updateData);

        $this->cache->delete(CacheKeys::config($config->group_key));

        return $config->fresh()->toArray();
    }
}
