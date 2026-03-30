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

class Create
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(array $data): array
    {
        $dictName = $data['dict_name'] ?? '';
        $dictCode = $data['dict_code'] ?? '';

        empty($dictName) && paramError('字典名称不能为空');
        empty($dictCode) && paramError('字典编码不能为空');

        $this->dictModel->where('dict_code', $dictCode)->exists()
            && paramError('字典编码已存在');

        $dict = $this->dictModel->create([
            'dict_name' => $dictName,
            'dict_code' => $dictCode,
            'dict_type' => $data['dict_type'] ?? 'default',
            'status' => $data['status'] ?? 1,
        ]);

        CacheKeys::flush(CacheKeys::DICT_TREE);

        return ['id' => $dict->id, 'dict_code' => $dictCode];
    }
}
