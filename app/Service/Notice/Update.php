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
use App\Service\Notice\Dto\UpdateNoticeDto;
use Hyperf\DbConnection\Db;
use Throwable;

class Update
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(UpdateNoticeDto $dto): bool
    {
        Db::beginTransaction();
        try {
            $notice = $this->noticeModel->find($dto->id);
            ! $notice && paramError('通知不存在');

            $updateData = $notice->status == 1
                ? $dto->getPublishedUpdateFields()
                : $dto->toUpdateArray();

            $notice->update($updateData);

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
