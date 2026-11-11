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

use App\Model\Notify\NoticeTargetModel;

class BatchPushToUsers
{
    public function __construct(
        protected NoticeTargetModel $targetModel,
    ) {
    }

    public function handle(array $data): int
    {
        $noticeId = (int) ($data['notice_id'] ?? 0);
        $targetUsers = $data['target_users'] ?? [];

        requireId($noticeId, '通知');
        ! empty($targetUsers) || paramError('目标用户不能为空');

        $targets = [];
        foreach ($targetUsers as $userId) {
            $targets[] = [
                'notice_id' => $noticeId,
                'target_type' => 4,
                'target_id' => $userId,
            ];
        }

        $this->targetModel->insert($targets);

        return count($targetUsers);
    }
}
