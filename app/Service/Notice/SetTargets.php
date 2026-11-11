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
use App\Model\Notify\NoticeTargetModel;

class SetTargets
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeTargetModel $targetModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $noticeId = (int) ($data['id'] ?? 0);
        $targetType = (int) ($data['target_type'] ?? 0);
        $targetIds = $data['target_ids'] ?? [];

        $notice = $this->noticeModel->findOrNotFound($noticeId, '通知');

        $this->targetModel->where('notice_id', $noticeId)->delete();

        if ($targetType == 1) {
            $targets = [[
                'notice_id' => $noticeId,
                'target_type' => $targetType,
                'target_id' => null,
            ]];
        } else {
            $targets = [];
            foreach ($targetIds as $targetId) {
                $targets[] = [
                    'notice_id' => $noticeId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                ];
            }
        }

        ! empty($targets) && $this->targetModel->insert($targets);

        return true;
    }
}
