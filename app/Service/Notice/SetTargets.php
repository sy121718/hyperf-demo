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
use App\Model\Notify\NoticeTargetModel;
use Hyperf\DbConnection\Db;

class SetTargets
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeTargetModel $targetModel,
    ) {
    }

    public function handle(NoticeRequest $request): bool
    {
        $noticeId = (int) $request->id;
        $targetType = (int) $request->target_type;
        $targetIds = $request->target_ids ?? [];

        $this->noticeModel->findOrNotFound($noticeId, '通知');

        $targets = [];
        if ($targetType === 1) {
            // 全体用户：target_id 列是 NOT NULL DEFAULT 0，用 0 表示不指定对象
            $targets[] = [
                'notice_id' => $noticeId,
                'target_type' => $targetType,
                'target_id' => 0,
            ];
        } else {
            foreach ($targetIds as $targetId) {
                $targets[] = [
                    'notice_id' => $noticeId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                ];
            }
        }

        // 先删后插必须在同一事务里，否则插入失败会留下「目标人群被清空」的中间态
        Db::transaction(function () use ($noticeId, $targets) {
            $this->targetModel->where('notice_id', $noticeId)->delete();

            if (! empty($targets)) {
                $this->targetModel->insert($targets);
            }
        });

        return true;
    }
}
