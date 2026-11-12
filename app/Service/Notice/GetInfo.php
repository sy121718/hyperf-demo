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

/**
 * 公告详情：列表接口不返回 content，编辑前必须走这里取正文。
 */
class GetInfo
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(int $id): array
    {
        $notice = $this->noticeModel->findOrNotFound($id, '通知');

        return $notice->toArray();
    }
}
