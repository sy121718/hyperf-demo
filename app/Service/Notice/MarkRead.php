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

class MarkRead
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $noticeId = (int) ($data['notice_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);

        $userId > 0 || paramError('用户ID不能为空');

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
