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

namespace App\Model\IpBlacklist;

use App\Common\Model\BaseModel;
use App\Model\Admin\SysAdminModel;

class IpBlacklistModel extends BaseModel
{
    public const STATUS_EXPIRED = 0;

    public const STATUS_ACTIVE = 1;

    public const STATUS_MANUAL_LIFTED = 2;

    protected ?string $table = 'ip_blacklist';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'ip_address', 'error_type', 'ban_reason', 'ban_duration',
        'banned_time', 'banned_until_time', 'ban_count', 'operator_id',
        'remark', 'status',
    ];

    protected array $casts = [
        'id' => 'integer',
        'ban_duration' => 'integer',
        'ban_count' => 'integer',
        'operator_id' => 'integer',
        'status' => 'integer',
    ];

    protected array $whereableFields = [
        'ip_address', 'error_type', 'status', 'operator_id',
    ];

    protected array $searchableFields = [
        'ip_address', 'ban_reason', 'remark',
    ];

    protected array $orderableFields = [
        'id', 'ip_address', 'ban_count', 'banned_time', 'banned_until_time', 'create_time',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('banned_until_time', '>', now());
    }

    public function scopeActiveForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip)
            ->where('status', self::STATUS_ACTIVE)
            ->where('banned_until_time', '>', now())
            ->orderByDesc('banned_until_time');
    }

    public function operator()
    {
        return $this->belongsTo(SysAdminModel::class, 'operator_id');
    }
}
