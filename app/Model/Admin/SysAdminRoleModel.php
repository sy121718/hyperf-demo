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
use App\Model\Role\SysRoleModel;

class SysAdminRoleModel extends BaseModel
{
    protected ?string $table = 'sys_admin_role';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'admin_code', 'role_code', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'admin_code', 'admin_code');
    }

    public function role()
    {
        return $this->belongsTo(SysRoleModel::class, 'role_code', 'role_code');
    }
}
