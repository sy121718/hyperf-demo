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

class Publish
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(array $data): bool
    {
        Db::beginTransaction();
        try {
            $notice = $this->noticeModel->findOrNotFound($data['id'], '通知');

            $notice->status == 1 && paramError('通知已发布');

            $publishTime = $data['publish_time'] ?? date('Y-m-d H:i:s');

            if (is_numeric($publishTime) && $publishTime > 1000000000) {
                $seconds = $publishTime > 1000000000000 ? $publishTime / 1000 : $publishTime;
                $publishTime = date('Y-m-d H:i:s', (int) $seconds);
            }

            $notice->update([
                'status' => 1,
                'publish_time' => $publishTime,
            ]);

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
