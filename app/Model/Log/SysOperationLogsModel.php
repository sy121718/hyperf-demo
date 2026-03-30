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

namespace App\Model\Log;

use App\Common\Model\BaseModel;
use App\Model\Admin\SysAdminModel;
use App\Model\Menu\SysMenuModel;

class SysOperationLogsModel extends BaseModel
{
    public const UPDATED_AT = null;

    public bool $timestamps = true;

    protected ?string $table = 'sys_operation_logs';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'admin_id', 'menu_id', 'perm_dict_id', 'service_method',
        'operation', 'api_path', 'http_method', 'request_data',
        'response_data', 'ip', 'location', 'user_agent',
        'result', 'content', 'execution_time', 'create_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'menu_id' => 'integer',
        'perm_dict_id' => 'integer',
        'result' => 'integer',
        'execution_time' => 'integer',
        'create_by' => 'integer',
    ];

    protected array $whereableFields = [
        'admin_id', 'menu_id', 'perm_dict_id', 'operation',
        'api_path', 'http_method', 'ip', 'result',
    ];

    protected array $searchableFields = [
        'operation', 'api_path', 'content',
    ];

    protected array $orderableFields = [
        'id', 'admin_id', 'operation', 'result', 'execution_time', 'create_time',
    ];

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'admin_id');
    }

    public function menu()
    {
        return $this->belongsTo(SysMenuModel::class, 'menu_id');
    }

    public function creator()
    {
        return $this->belongsTo(SysAdminModel::class, 'create_by');
    }
}
