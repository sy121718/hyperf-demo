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

use App\Dto\Log\LogRequest;
use App\Model\Admin\SysLoginLogModel;

class GetList
{
    public function __construct(
        protected SysLoginLogModel $loginLogModel,
    ) {
    }

    public function handle(LogRequest $request): mixed
    {
        // toQueryArray 已含 user_id，filter 作用域按模型白名单处理
        return $this->loginLogModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->loginLogModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->when($request->date_from, fn ($q, $v) => $q->where('login_time', '>=', $v . ' 00:00:00.000'))
            ->when($request->date_to, fn ($q, $v) => $q->where('login_time', '<=', $v . ' 23:59:59.999'))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
