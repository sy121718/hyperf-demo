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

namespace App\Model\Config;

use App\Common\Model\BaseModel;

class SysConfigModel extends BaseModel
{
    protected ?string $table = 'sys_config';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'group_key', 'group_name', 'config_data', 'remark',
        'status', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'config_data' => 'json',
        'status' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'group_key', 'group_name', 'status',
    ];

    protected array $searchableFields = [
        'group_key', 'group_name', 'remark',
    ];

    protected array $orderableFields = [
        'id', 'create_time', 'update_time',
    ];
}
