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

class SysOperationLogModel extends BaseModel
{
    public bool $timestamps = false;

    protected ?string $table = 'sys_operation_logs';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'admin_id', 'menu_id', 'perm_dict_id', 'service_method',
        'operation', 'api_path', 'http_method', 'request_data',
        'response_data', 'ip', 'location', 'user_agent',
        'result', 'content', 'execution_time', 'create_by', 'create_time',
    ];

    protected array $casts = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'menu_id' => 'integer',
        'perm_dict_id' => 'integer',
        'request_data' => 'json',
        'response_data' => 'json',
        'result' => 'integer',
        'execution_time' => 'integer',
        'create_by' => 'integer',
    ];

    protected array $whereableFields = [
        'admin_id', 'result', 'http_method',
    ];

    protected array $searchableFields = [
        'operation', 'api_path', 'ip', 'content',
    ];

    protected array $orderableFields = [
        'id', 'create_time', 'execution_time',
    ];

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'admin_id');
    }
}
