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
use App\Model\Notify\NoticeReadModel;

class GetUnreadCount
{
    public function __construct(
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
    ) {
    }

    /**
     * 未读数 = 当前已发布、且该用户没有已读记录的通知条数.
     *
     * 不能写成「已发布总数 - 该用户已读记录数」：read 记录不随通知过期而失效，
     * 一条通知过期后不再计入已发布，它的 read 记录却还在被减掉，差值会被压低，
     * 用户看到的未读数小于实际。用 NOT EXISTS 直接数「没读过的已发布通知」，
     * 两个集合天然同口径。
     */
    public function handle(int $userId): int
    {
        requireId($userId, '用户');

        return (int) $this->noticeModel
            ->published()
            ->whereNotExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from($this->readModel->getTable())
                    ->whereColumn($this->readModel->getTable() . '.notice_id', $this->noticeModel->getTable() . '.id')
                    ->where($this->readModel->getTable() . '.user_id', $userId);
            })
            ->count();
    }
}
