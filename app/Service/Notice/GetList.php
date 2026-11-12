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
 * 管理端公告列表：含草稿与已撤回，按筛选条件分页。
 */
class GetList
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(NoticeRequest $request): mixed
    {
        return $this->noticeModel
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
