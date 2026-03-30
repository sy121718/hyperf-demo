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
use App\Model\IpBlacklist\IpBlacklistModel;
use Carbon\Carbon;

class Ban
{
    public function __construct(
        protected IpBlacklistModel $model,
    ) {
    }

    public function handle(array $data): array
    {
        $ipAddress = $data['ip_address'] ?? '';
        $errorType = $data['error_type'] ?? 'other';
        $banDuration = (int) ($data['ban_duration'] ?? 30);

        empty($ipAddress) && paramError('IP地址不能为空');

        $existingBan = $this->model->activeForIp($ipAddress)->first();

        if ($existingBan) {
            $currentUntil = Carbon::parse($existingBan->banned_until_time);
            $newUntil = $currentUntil->addMinutes($banDuration);

            $existingBan->update([
                'banned_until_time' => $newUntil,
                'ban_count' => $existingBan->ban_count + 1,
            ]);

            CacheKeys::delete(CacheKeys::ipBlacklist($ipAddress));

            return $existingBan->fresh()->toArray();
        }

        $bannedTime = Carbon::now();
        $bannedUntil = $bannedTime->copy()->addMinutes($banDuration);

        $record = $this->model->create([
            'ip_address' => $ipAddress,
            'error_type' => $errorType,
            'ban_reason' => $data['ban_reason'] ?? null,
            'ban_duration' => $banDuration,
            'banned_time' => $bannedTime,
            'banned_until_time' => $bannedUntil,
            'ban_count' => 1,
            'operator_id' => $data['operator_id'] ?? currentUser('id'),
            'remark' => $data['remark'] ?? null,
            'status' => IpBlacklistModel::STATUS_ACTIVE,
        ]);

        CacheKeys::delete(CacheKeys::ipBlacklist($ipAddress));

        return $record->toArray();
    }
}
