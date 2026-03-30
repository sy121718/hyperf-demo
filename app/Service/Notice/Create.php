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
use App\Service\Notice\Dto\CreateNoticeDto;
use Hyperf\DbConnection\Db;
use Throwable;

class Create
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(CreateNoticeDto $dto): int
    {
        Db::beginTransaction();
        try {
            $dbData = $dto->toDbArray();

            if (! empty($dbData['expire_time'])) {
                $dbData['expire_time'] = $this->convertExpireTime($dbData['expire_time']);
            }

            $dbData['create_by'] = $dbData['create_by'] ?? (int) currentUser('id');

            $notice = $this->noticeModel->create($dbData);
            ! $notice && paramError('创建通知失败');

            Db::commit();
            return $notice->id;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function convertExpireTime(?string $expireTime): ?string
    {
        if ($expireTime === null || $expireTime === '') {
            return null;
        }

        if (is_numeric($expireTime) && (int) $expireTime > 1000000000) {
            $seconds = (int) $expireTime > 1000000000000 ? (int) $expireTime / 1000 : (int) $expireTime;
            return date('Y-m-d H:i:s', (int) $seconds);
        }

        return $expireTime;
    }
}
