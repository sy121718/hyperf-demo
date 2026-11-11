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

use function Hyperf\Coroutine\parallel;

class GetUnreadCount
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    public function handle(int $userId): int
    {
        requireId($userId, '用户');

        [$totalCount, $readCount] = parallel([
            fn () => $this->noticeModel
                ->where('status', 1)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->count(),

            fn () => $this->readModel
                ->where('user_id', $userId)
                ->count(),
        ]);

        return max(0, $totalCount - $readCount);
    }
}
