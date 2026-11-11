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
use Hyperf\DbConnection\Db;
use Throwable;

class Update
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(NoticeRequest $request): bool
    {
        Db::beginTransaction();
        try {
            $id = (int) $request->id;
            $notice = $this->noticeModel->find($id);
            ! $notice && notFound('通知');

            $updateData = $request->toDbArrayFiltered();

            // 已发布的公告只允许改置顶/弹窗/过期时间
            if ((int) $notice->status === 1) {
                $updateData = array_intersect_key($updateData, array_flip(['is_top', 'is_popup', 'expire_time']));
            }

            if (array_key_exists('expire_time', $updateData)) {
                $updateData['expire_time'] = $this->convertExpireTime($request->expire_time);
            }

            $notice->update($updateData);

            Db::commit();
            return true;
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
