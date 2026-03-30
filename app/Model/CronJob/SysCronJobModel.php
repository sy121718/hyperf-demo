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

namespace App\Model\CronJob;

use App\Common\Model\BaseModel;
use App\Model\Admin\SysAdminModel;

class SysCronJobModel extends BaseModel
{
    protected ?string $table = 'sys_cron_job';

    protected array $fillable = [
        'job_name', 'job_category', 'description', 'cron_expression',
        'command', 'status', 'sort_order', 'last_sync_time',
        'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'sort_order' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'job_category', 'status',
    ];

    protected array $searchableFields = [
        'job_name', 'description', 'command',
    ];

    protected array $orderableFields = [
        'id', 'sort_order', 'last_sync_time', 'create_time', 'update_time',
    ];

    public function creator()
    {
        return $this->belongsTo(SysAdminModel::class, 'create_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(SysAdminModel::class, 'update_by', 'id');
    }
}
