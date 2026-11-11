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
use App\Dto\Dict\DictRequest;
use App\Model\System\SysDictModel;

class Create
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(DictRequest $request): array
    {
        $dictCode = (string) $request->dict_code;

        $this->dictModel->where('dict_code', $dictCode)->exists()
            && paramError('字典编码已存在');

        $dbData = $request->toDbArrayFiltered();
        $dbData['dict_type'] ??= 'default';
        $dbData['status'] ??= 1;

        $dict = $this->dictModel->create($dbData);

        CacheKeys::flush(CacheKeys::DICT_TREE);

        return $dict->toArray();
    }
}
