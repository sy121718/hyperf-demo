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

namespace App\Model\Email;

use App\Common\Model\BaseModel;
use App\Model\Admin\SysAdminModel;

class EmailTemplateModel extends BaseModel
{
    protected ?string $table = 'email_template';

    protected array $fillable = [
        'template_name', 'template_code', 'subject', 'content',
        'template_type', 'variables', 'status', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'variables' => 'json',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'status', 'template_type',
    ];

    protected array $searchableFields = [
        'template_name', 'template_code', 'subject',
    ];

    protected array $orderableFields = [
        'id', 'template_name', 'template_code', 'status', 'create_time', 'update_time',
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
