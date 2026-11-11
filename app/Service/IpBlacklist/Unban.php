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

namespace App\Service\IpBlacklist;

use App\Common\CacheKeys;
use App\Model\System\IpBlacklistModel;

class Unban
{
    public function __construct(
        protected IpBlacklistModel $model,
    ) {
    }

    public function handle(int $id): array
    {
        $record = $this->model->findOrNotFound($id, '拉黑记录');

        $record->status !== IpBlacklistModel::STATUS_ACTIVE
            && paramError('该记录已失效或已被解除');

        $record->update(['status' => IpBlacklistModel::STATUS_MANUAL_LIFTED]);

        CacheKeys::delete(CacheKeys::ipBlacklist($record->ip_address));

        return $record->fresh()->toArray();
    }

    public function unbanByIp(string $ipAddress): int
    {
        $count = $this->model
            ->where('ip_address', $ipAddress)
            ->where('status', IpBlacklistModel::STATUS_ACTIVE)
            ->update(['status' => IpBlacklistModel::STATUS_MANUAL_LIFTED]);

        CacheKeys::delete(CacheKeys::ipBlacklist($ipAddress));

        return $count;
    }
}
