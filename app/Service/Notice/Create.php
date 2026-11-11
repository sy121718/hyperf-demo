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
use App\Model\Notice\NoticeModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Create
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(NoticeRequest $request): int
    {
        Db::beginTransaction();
        try {
            $dbData = $request->toDbArrayFiltered();
            $dbData['expire_time'] = $this->convertExpireTime($request->expire_time);

            $notice = $this->noticeModel->create($dbData);
            ! $notice && paramError('创建通知失败');

            Db::commit();
            return (int) $notice->id;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 兼容时间戳与日期字符串，统一存成 Y-m-d H:i:s.
     */
    private function convertExpireTime(mixed $expireTime): ?string
    {
        if ($expireTime === null || $expireTime === '') {
            return null;
        }

        if (is_numeric($expireTime) && (int) $expireTime > 1000000000) {
            $seconds = (int) $expireTime > 1000000000000 ? (int) $expireTime / 1000 : (int) $expireTime;
            return date('Y-m-d H:i:s', (int) $seconds);
        }

        return (string) $expireTime;
    }
}
