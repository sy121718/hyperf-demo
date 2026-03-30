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

namespace App\Model\Menu;

use App\Common\Model\BaseModel;
use App\Model\Role\SysRoleModel;
use Hyperf\Database\Model\SoftDeletes;

class SysMenuModel extends BaseModel
{
    use SoftDeletes;

    protected const DELETED_AT = 'deleted_time';

    protected ?string $table = 'sys_menus';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'menu_code', 'menu_name', 'parent_id', 'type', 'title',
        'path', 'component', 'external_url', 'icon', 'status',
        'is_hidden', 'is_public', 'permission_code', 'sort_order',
        'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'is_hidden' => 'integer',
        'is_public' => 'integer',
        'sort_order' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'parent_id', 'type', 'status', 'is_hidden', 'is_public',
    ];

    protected array $searchableFields = [
        'menu_code', 'menu_name', 'title', 'path',
    ];

    protected array $orderableFields = [
        'id', 'sort_order', 'create_time',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function roles()
    {
        return $this->belongsToMany(
            SysRoleModel::class,
            'sys_role_menu',
            'menu_id',
            'role_id'
        );
    }
}
