<?php

declare(strict_types=1);

namespace App\Model\Admin;

use App\Common\Model\BaseModel;
use Hyperf\Database\Model\SoftDeletes;

class SysAdminModel extends BaseModel
{
    use SoftDeletes;

    protected ?string $table = 'sys_admin';
    protected ?string $dateFormat = 'Y-m-d H:i:s.v';
    protected const DELETED_AT = 'deleted_time';

    protected array $fillable = [
        'username', 'admin_code', 'password', 'name', 'avatar', 'email', 'phone',
        'status', 'is_admin', 'login_failure_count', 'locked_until_time',
        'last_failure_time', 'register_ip', 'register_location',
        'last_login_ip', 'last_login_location', 'last_login_isp',
        'last_login_time', 'create_by', 'update_by',
    ];

    protected array $hidden = ['password'];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'is_admin' => 'integer',
        'login_failure_count' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'username', 'admin_code', 'status', 'is_admin', 'email', 'phone',
    ];

    protected array $searchableFields = [
        'username', 'name', 'email', 'phone',
    ];

    protected array $orderableFields = [
        'id', 'username', 'name', 'status', 'create_time', 'update_time', 'last_login_time',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            \App\Model\Role\SysRoleModel::class,
            'sys_admin_role',
            'admin_code',
            'role_code'
        );
    }

    public function sessions()
    {
        return $this->hasMany(SysAdminSessionsModel::class, 'admin_id');
    }
}
