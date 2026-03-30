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
use App\Model\Notice\NoticeTargetModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeTargetModel $targetModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    public function handle(int $id): bool
    {
        Db::beginTransaction();
        try {
            $notice = $this->noticeModel->find($id);
            ! $notice && paramError('通知不存在');

            $notice->status == 1 && paramError('已发布的通知不能删除');

            $this->targetModel->where('notice_id', $id)->delete();
            $this->readModel->where('notice_id', $id)->delete();
            $notice->delete();

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
