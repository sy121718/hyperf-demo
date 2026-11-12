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
use App\Model\Rbac\SysRoleModel;
use Hyperf\Database\Model\SoftDeletes;

class SysAdminModel extends BaseModel
{
    use SoftDeletes;

    protected const DELETED_AT = 'deleted_time';

    protected ?string $table = 'sys_admin';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

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
            SysRoleModel::class,
            'sys_admin_role',
            'admin_code',
            'role_code',
            'admin_code',
            'role_code'
        );
    }

    /**
     * 是否已有其他未删除的超级管理员.
     *
     * 与数据库唯一索引 uk_super_slot 口径一致：软删除的不算占用名额。
     * 这里只做友好报错，真正的强约束在数据库层。
     *
     * @param null|int $excludeId 排除的管理员 ID（更新场景排除自己）
     */
    public function hasOtherSuperAdmin(?int $excludeId = null): bool
    {
        return static::query()
            ->where('is_admin', 1)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function sessions()
    {
        return $this->hasMany(SysAdminSessionsModel::class, 'admin_id');
    }

    /**
     * 该管理员启用角色下的全部权限码（已去重）.
     *
     * 全项目唯一的权限码解析入口：菜单过滤与权限中间件都走这里，
     * 避免各写一套导致「看得到菜单但接口 403」这类口径分叉。
     * sys_role.permission_codes 已配 array cast，拿到的是数组，不需要手动 json_decode。
     *
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        return $this->roles()
            ->where('status', 1)
            ->get()
            ->flatMap(static fn (SysRoleModel $role) => $role->permission_codes ?? [])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 按 admin_code 取权限码，供拿不到模型实例的调用方（如中间件）使用.
     *
     * @return array<int, string>
     */
    public static function permissionCodesByAdminCode(string $adminCode): array
    {
        return static::query()
            ->where('admin_code', $adminCode)
            ->first()
            ?->permissionCodes() ?? [];
    }

    /**
     * 新建时自动生成 admin_code（表字段 NOT NULL 且唯一）.
     */
    public function save(array $options = []): bool
    {
        if (! $this->exists && empty($this->getAttribute('admin_code'))) {
            $this->setAttribute('admin_code', $this->generateAdminCode());
        }

        return parent::save($options);
    }

    /**
     * 生成唯一的管理员标识码。
     */
    protected function generateAdminCode(): string
    {
        do {
            $code = 'a' . bin2hex(random_bytes(8));
        } while (static::query()->where('admin_code', $code)->exists());

        return $code;
    }
}
