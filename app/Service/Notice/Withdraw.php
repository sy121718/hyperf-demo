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
use Hyperf\DbConnection\Db;
use Throwable;

class Withdraw
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(int $id): bool
    {
        Db::beginTransaction();
        try {
            $notice = $this->noticeModel->findOrNotFound($id, '通知');

            $notice->status != 1 && paramError('只能撤回已发布的通知');

            $notice->update(['status' => 2]);

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
