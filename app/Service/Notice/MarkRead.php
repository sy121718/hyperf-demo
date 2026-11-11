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

class MarkRead
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    public function handle(NoticeRequest $request): bool
    {
        $noticeId = (int) $request->notice_id;
        $userId = (int) $request->user_id;

        requireId($userId, '用户');

        $exists = $this->readModel
            ->where('notice_id', $noticeId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            $this->readModel->create([
                'notice_id' => $noticeId,
                'user_id' => $userId,
                'read_time' => date('Y-m-d H:i:s.v'),
            ]);

            $this->noticeModel->where('id', $noticeId)->increment('read_count');
        }

        return true;
    }
}
