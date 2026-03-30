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

namespace App\Service\Ops\LoginLog;

use App\Model\Log\SysLoginLogModel;

class GetList
{
    public function __construct(
        protected SysLoginLogModel $loginLogModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'user_id', 'username', 'status', 'ip_address', 'location',
            'user_agent', 'device_type', 'login_time',
        ];

        $query = $this->loginLogModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->loginLogModel->getSearchableFields(), 'like', "%{$search}%"))
            ->when(isset($data['date_from']), fn ($q) => $q->where('login_time', '>=', $data['date_from'] . ' 00:00:00.000'))
            ->when(isset($data['date_to']), fn ($q) => $q->where('login_time', '<=', $data['date_to'] . ' 23:59:59.999'))
            ->sort($data);

        // 条件加载关联
        if ($withDetail) {
            $query->with(['admin:id,username,name']);
        }

        return $query->paginate($data['limit'] ?? 10, $fields, 'page', $data['page'] ?? 1);
    }
}
