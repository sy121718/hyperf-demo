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

use App\Model\Config\SysConfigModel;

class GetList
{
    public function __construct(
        protected SysConfigModel $configModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'group_key', 'group_name', 'config_data', 'remark', 'status', 'create_time',
        ];

        return $this->configModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->configModel->getSearchableFields(), 'like', "%{$search}%"))
            ->sort($data)
            ->paginate($data['limit'] ?? 10, $fields, 'page', $data['page'] ?? 1);
    }
}
