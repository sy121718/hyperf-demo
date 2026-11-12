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

namespace App\Service\Notice;

use App\Dto\Notice\NoticeRequest;
use App\Model\Notify\NoticeModel;

/**
 * 我的公告列表：只查当前登录用户创建的公告。
 *
 * 与 GetList 的区别仅是多一个 create_by 过滤，不参与任何权限判断——
 * 谁能看全量由 PermissionMiddleware 的 notice:list 权限点决定。
 */
class GetMyList
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(NoticeRequest $request): mixed
    {
        return $this->noticeModel
            ->where('create_by', (int) currentUser('id'))
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->noticeModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
