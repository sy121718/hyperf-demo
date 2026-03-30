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

namespace App\Model\Dict;

use App\Common\Model\BaseModel;

class SysDictModel extends BaseModel
{
    protected ?string $table = 'sys_dict';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'dict_name', 'dict_code', 'dict_type', 'status',
        'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'dict_name', 'dict_code', 'dict_type', 'status',
    ];

    protected array $searchableFields = [
        'dict_name', 'dict_code',
    ];

    protected array $orderableFields = [
        'id', 'create_time', 'update_time',
    ];

    public function items()
    {
        return $this->hasMany(SysDictItemModel::class, 'dict_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }
}
