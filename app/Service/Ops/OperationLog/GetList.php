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

namespace App\Service\Ops\OperationLog;

use App\Model\Admin\SysOperationLogModel;

class GetList
{
    public function __construct(
        protected SysOperationLogModel $operationLogModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        $resultValue = null;
        if (isset($data['result'])) {
            $resultValue = $data['result'] === 'success' ? 1 : 0;
            unset($data['result']);
        }

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'admin_id', 'menu_id', 'operation', 'service_method', 'api_path',
            'http_method', 'ip', 'location', 'result', 'create_time',
        ];

        $query = $this->operationLogModel
            ->when($resultValue !== null, fn ($q) => $q->where('result', $resultValue))
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->operationLogModel->getSearchableFields(), 'like', "%{$search}%"))
            ->when(isset($data['date_from']), fn ($q) => $q->where('create_time', '>=', $data['date_from'] . ' 00:00:00.000'))
            ->when(isset($data['date_to']), fn ($q) => $q->where('create_time', '<=', $data['date_to'] . ' 23:59:59.999'))
            ->sort($data);

        // 条件加载关联
        if ($withDetail) {
            $query->with(['admin:id,username,name', 'menu:id,menu_name', 'creator:id,username']);
        }

        return $query->paginate((int) ($data['limit'] ?? 10), $fields, 'page', (int) ($data['page'] ?? 1));
    }
}
