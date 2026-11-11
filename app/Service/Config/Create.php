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

use App\Model\System\SysConfigModel;

class Create
{
    public function __construct(
        protected SysConfigModel $configModel,
    ) {
    }

    public function handle(array $data): array
    {
        $groupKey = $data['group_key'] ?? '';
        $groupName = $data['group_name'] ?? '';

        empty($groupKey) && paramError('配置分组键不能为空');
        empty($groupName) && paramError('配置分组名称不能为空');

        $this->configModel->where('group_key', $groupKey)->exists()
            && paramError('配置分组键已存在');

        $config = $this->configModel->create([
            'group_key' => $groupKey,
            'group_name' => $groupName,
            'config_data' => $data['config_data'] ?? [],
            'remark' => $data['remark'] ?? null,
            'status' => $data['status'] ?? 1,
        ]);

        return $config->toArray();
    }
}
