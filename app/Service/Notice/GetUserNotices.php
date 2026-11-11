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
use App\Model\Notify\NoticeReadModel;
use Hyperf\Contract\LengthAwarePaginatorInterface;

class GetUserNotices
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    /**
     * 返回分页器本身，由响应层裁剪成 {total, list}.
     */
    public function handle(NoticeRequest $request, int $userId): LengthAwarePaginatorInterface
    {
        $paginator = $this->noticeModel
            ->published()
            ->when($request->notice_type, fn ($q, $v) => $q->where('notice_type', $v))
            ->orderBy('is_top', 'desc')
            ->orderBy('publish_time', 'desc')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());

        if ($userId <= 0) {
            return $paginator;
        }

        $readNoticeIds = $this->readModel
            ->where('user_id', $userId)
            ->pluck('notice_id')
            ->toArray();

        $paginator->getCollection()->transform(function ($item) use ($readNoticeIds) {
            $item->is_read = in_array($item->id, $readNoticeIds) ? 1 : 0;
            return $item;
        });

        return $paginator;
    }
}
