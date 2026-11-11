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
    public function handle(array $data): LengthAwarePaginatorInterface
    {
        $page = (int) ($data['page'] ?? 1);
        $limit = (int) ($data['limit'] ?? 10);
        $userId = (int) ($data['user_id'] ?? 0);
        $noticeType = $data['notice_type'] ?? null;

        $paginator = $this->noticeModel
            ->published()
            ->when($noticeType, fn ($q, $v) => $q->where('notice_type', $v))
            ->orderBy('is_top', 'desc')
            ->orderBy('publish_time', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

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
