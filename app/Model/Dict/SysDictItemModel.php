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

class SysDictItemModel extends BaseModel
{
    protected ?string $table = 'sys_dict_item';

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected array $fillable = [
        'dict_id', 'item_label', 'item_value', 'sort_order',
        'status', 'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'dict_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'dict_id', 'item_label', 'item_value', 'status',
    ];

    protected array $searchableFields = [
        'item_label', 'item_value',
    ];

    protected array $orderableFields = [
        'id', 'sort_order', 'create_time',
    ];

    public function dict()
    {
        return $this->belongsTo(SysDictModel::class, 'dict_id');
    }
}
