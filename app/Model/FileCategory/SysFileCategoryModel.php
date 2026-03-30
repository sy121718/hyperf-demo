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

namespace App\Model\FileCategory;

use App\Common\Model\BaseModel;

class SysFileCategoryModel extends BaseModel
{
    protected ?string $table = 'sys_file_category';

    protected array $fillable = [
        'category_name', 'category_code', 'parent_id', 'sort_order',
        'icon', 'status', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'parent_id', 'status',
    ];

    protected array $searchableFields = [
        'category_name', 'category_code',
    ];

    protected array $orderableFields = [
        'id', 'category_name', 'sort_order', 'status', 'create_time', 'update_time',
    ];
}
