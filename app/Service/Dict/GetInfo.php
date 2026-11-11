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

namespace App\Service\Dict;

use App\Model\System\SysDictModel;

class GetInfo
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(int $id): array
    {
        requireId($id, '字典');

        $dict = $this->dictModel->with(['items'])->find($id);
        ! $dict && notFound('字典');

        return [
            'id' => $dict->id,
            'dict_name' => $dict->dict_name,
            'dict_code' => $dict->dict_code,
            'dict_type' => $dict->dict_type ?? 'default',
            'status' => $dict->status,
            'create_time' => $dict->create_time,
            'items' => $dict->items->map(fn ($item) => [
                'id' => $item->id,
                'item_label' => $item->item_label,
                'item_value' => $item->item_value,
                'sort_order' => $item->sort_order,
                'status' => $item->status,
            ])->toArray(),
        ];
    }
}
