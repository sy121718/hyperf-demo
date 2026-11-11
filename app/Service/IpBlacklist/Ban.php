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
use App\Dto\IpBlacklist\IpBlacklistRequest;
use App\Model\System\IpBlacklistModel;
use Carbon\Carbon;

class Ban
{
    public function __construct(
        protected IpBlacklistModel $model,
    ) {
    }

    public function handle(IpBlacklistRequest $request): array
    {
        $ipAddress = (string) $request->ip_address;
        $banDuration = (int) ($request->ban_duration ?? 30);

        $existingBan = $this->model->activeForIp($ipAddress)->first();

        // 重复拉黑：在原到期时间上顺延，并累加次数
        if ($existingBan) {
            $existingBan->update([
                'banned_until_time' => Carbon::parse($existingBan->banned_until_time)->addMinutes($banDuration),
                'ban_count' => $existingBan->ban_count + 1,
            ]);

            CacheKeys::delete(CacheKeys::ipBlacklist($ipAddress));

            return $existingBan->fresh()->toArray();
        }

        $bannedTime = Carbon::now();

        $dbData = $request->toDbArrayFiltered();
        $dbData['error_type'] ??= 'other';
        $dbData['ban_duration'] = $banDuration;
        $dbData['banned_time'] = $bannedTime;
        $dbData['banned_until_time'] = $bannedTime->copy()->addMinutes($banDuration);
        $dbData['ban_count'] = 1;
        $dbData['operator_id'] = currentUser('id');
        $dbData['status'] = IpBlacklistModel::STATUS_ACTIVE;

        $record = $this->model->create($dbData);

        CacheKeys::delete(CacheKeys::ipBlacklist($ipAddress));

        return $record->toArray();
    }
}
