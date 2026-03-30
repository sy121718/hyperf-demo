<?php

declare(strict_types=1);

namespace App\Model\Role;

use App\Common\Model\BaseModel;
use Hyperf\Database\Model\SoftDeletes;

class SysRoleModel extends BaseModel
{
    use SoftDeletes;

    protected ?string $table = 'sys_role';
    protected ?string $dateFormat = 'Y-m-d H:i:s.v';
    protected const DELETED_AT = 'deleted_time';

    protected array $fillable = [
        'role_name', 'role_code', 'role_type', 'sort_order',
        'status', 'remark', 'permission_codes', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'permission_codes' => 'array',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'role_name', 'role_code', 'role_type', 'status',
    ];

    protected array $searchableFields = [
        'role_name', 'role_code', 'remark',
    ];

    protected array $orderableFields = [
        'id', 'role_name', 'sort_order', 'status', 'create_time',
    ];

    public function admins()
    {
        return $this->belongsToMany(
            \App\Model\Admin\SysAdminModel::class,
            'sys_admin_role',
            'role_code',
            'admin_code'
        );
    }
}
