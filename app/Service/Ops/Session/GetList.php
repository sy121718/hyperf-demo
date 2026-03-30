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

namespace App\Service\Ops\Session;

use App\Model\Admin\SysAdminSessionsModel;

class GetList
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'admin_id', 'ip', 'location', 'isp', 'user_agent',
            'last_active_time', 'login_time', 'create_time',
        ];

        $query = $this->sessionModel
            ->where('status', 1)
            ->when($data['username'] ?? null, fn ($q, $v) => $q->whereHas('admin', fn ($sub) => $sub->where('username', 'like', "%{$v}%")))
            ->orderBy('last_active_time', 'desc');

        // 条件加载关联
        if ($withDetail) {
            $query->with(['admin:id,username,name']);
        }

        return $query->paginate($data['limit'] ?? 10, $fields, 'page', $data['page'] ?? 1);
    }
}
