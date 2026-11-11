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

namespace App\Model\Admin;

use App\Common\Model\BaseModel;

class SysLoginLogModel extends BaseModel
{
    public bool $timestamps = false;

    protected ?string $table = 'sys_login_log';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'user_id', 'username', 'ip_address', 'location', 'user_agent',
        'device_type', 'login_time', 'status', 'msg', 'session_id',
    ];

    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];

    protected array $whereableFields = [
        'user_id', 'username', 'ip_address', 'device_type', 'status',
    ];

    protected array $searchableFields = [
        'username', 'ip_address', 'location',
    ];

    protected array $orderableFields = [
        'id', 'login_time',
    ];

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'user_id');
    }
}
