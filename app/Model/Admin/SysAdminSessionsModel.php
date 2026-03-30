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

class SysAdminSessionsModel extends BaseModel
{
    protected ?string $table = 'sys_admin_sessions';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'admin_id', 'device_type', 'device_info', 'jwt_token',
        'login_ip', 'login_time', 'last_active_time', 'expires_time',
        'status', 'create_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'status' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'admin_id');
    }
}
