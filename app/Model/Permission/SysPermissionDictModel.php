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

namespace App\Model\Permission;

use App\Common\Model\BaseModel;
use App\Model\Menu\SysMenuModel;

class SysPermissionDictModel extends BaseModel
{
    public bool $timestamps = true;

    protected ?string $table = 'sys_permission_dict';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'perm_name', 'perm_code', 'parent_id', 'api_path',
        'http_method', 'controller_method', 'is_public',
        'rate_limit', 'route_priority', 'remark', 'status',
        'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'is_public' => 'integer',
        'rate_limit' => 'integer',
        'route_priority' => 'integer',
        'status' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'perm_code', 'http_method', 'is_public', 'status', 'parent_id',
    ];

    protected array $searchableFields = [
        'perm_name', 'perm_code', 'api_path', 'remark',
    ];

    protected array $orderableFields = [
        'id', 'perm_name', 'route_priority', 'status', 'create_time',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function menus()
    {
        return $this->belongsToMany(
            SysMenuModel::class,
            'sys_menu_permission',
            'permission_id',
            'menu_id'
        );
    }
}
