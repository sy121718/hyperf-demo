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

use App\Model\Notice\NoticeModel;
use App\Model\Notice\NoticeReadModel;

use function Hyperf\Coroutine\parallel;

class GetUserNotices
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    public function handle(array $data): array
    {
        $page = (int) ($data['page'] ?? 1);
        $limit = (int) ($data['limit'] ?? 10);
        $userId = (int) ($data['user_id'] ?? 0);
        $noticeType = $data['notice_type'] ?? null;

        if ($userId > 0) {
            [$result, $readNoticeIds] = parallel([
                fn () => $this->noticeModel
                    ->where('status', 1)
                    ->where('expire_time', '>', date('Y-m-d H:i:s'))
                    ->when($noticeType, fn ($q, $v) => $q->where('notice_type', $v))
                    ->orderBy('is_top', 'desc')
                    ->orderBy('publish_time', 'desc')
                    ->paginate($limit, ['*'], 'page', $page),

                fn () => $this->readModel
                    ->where('user_id', $userId)
                    ->pluck('notice_id')
                    ->toArray(),
            ]);

            $result->getCollection()->transform(function ($item) use ($readNoticeIds) {
                $item->is_read = in_array($item->id, $readNoticeIds) ? 1 : 0;
                return $item;
            });

            return $result->toArray();
        }

        return $this->noticeModel
            ->where('status', 1)
            ->where('expire_time', '>', date('Y-m-d H:i:s'))
            ->when($noticeType, fn ($q, $v) => $q->where('notice_type', $v))
            ->orderBy('is_top', 'desc')
            ->orderBy('publish_time', 'desc')
            ->paginate($limit, ['*'], 'page', $page)
            ->toArray();
    }
}
