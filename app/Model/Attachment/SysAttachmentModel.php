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

namespace App\Model\Attachment;

use App\Common\Model\BaseModel;
use App\Model\Admin\SysAdminModel;
use App\Model\FileCategory\SysFileCategoryModel;

class SysAttachmentModel extends BaseModel
{
    protected ?string $table = 'sys_attachment';

    protected array $fillable = [
        'category_id', 'file_name', 'file_path', 'file_size', 'file_type',
        'mime_type', 'storage_type', 'storage_path', 'url', 'md5',
        'extra_info', 'status', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'file_size' => 'integer',
        'status' => 'integer',
        'extra_info' => 'json',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'category_id', 'file_type', 'storage_type', 'status',
    ];

    protected array $searchableFields = [
        'file_name', 'mime_type',
    ];

    protected array $orderableFields = [
        'id', 'file_name', 'file_size', 'file_type', 'status', 'create_time', 'update_time',
    ];

    public function category()
    {
        return $this->belongsTo(SysFileCategoryModel::class, 'category_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(SysAdminModel::class, 'create_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(SysAdminModel::class, 'update_by', 'id');
    }
}
