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

namespace App\Service\IpBlacklist;

use App\Model\IpBlacklist\IpBlacklistModel;

class GetList
{
    public function __construct(
        protected IpBlacklistModel $model,
    ) {
    }

    public function handle(array $data): mixed
    {
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'ip_address', 'error_type', 'status', 'banned_time', 'create_time',
        ];

        $query = $this->model
            ->when($data['ip_address'] ?? null, fn ($q, $v) => $q->where('ip_address', 'like', "%{$v}%"))
            ->when(isset($data['error_type']), fn ($q) => $q->where('error_type', $data['error_type']))
            ->when(isset($data['status']), fn ($q) => $q->where('status', (int) $data['status']))
            ->when($data['start_date'] ?? null, fn ($q, $v) => $q->where('banned_time', '>=', $v))
            ->when($data['end_date'] ?? null, fn ($q, $v) => $q->where('banned_time', '<=', $v))
            ->orderByDesc('banned_time');

        // 条件加载关联
        if ($withDetail) {
            $query->with(['operator:id,username']);
        }

        return $query->paginate($data['limit'] ?? 20, $fields, 'page', $data['page'] ?? 1);
    }
}
