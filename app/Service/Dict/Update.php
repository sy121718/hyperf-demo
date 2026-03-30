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

use App\Common\CacheKeys;
use App\Model\Dict\SysDictModel;

class Update
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(int $id, array $data): array
    {
        $id > 0 || paramError('字典ID不能为空');

        $dict = $this->dictModel->find($id);
        ! $dict && paramError('字典不存在', 404);

        if (! empty($data['dict_code']) && $data['dict_code'] !== $dict->dict_code) {
            $this->dictModel->where('dict_code', $data['dict_code'])
                ->where('id', '!=', $id)->exists()
                && paramError('字典编码已存在');
        }

        $updateData = array_filter([
            'dict_name' => $data['dict_name'] ?? null,
            'dict_code' => $data['dict_code'] ?? null,
            'dict_type' => $data['dict_type'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($v) => $v !== null);

        $dict->update($updateData);

        CacheKeys::flush(CacheKeys::DICT_TREE, CacheKeys::dictItems($id));

        return $dict->fresh()->toArray();
    }
}
