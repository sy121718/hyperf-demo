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

class Delete
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $id > 0 || paramError('字典ID不能为空');

        $dict = $this->dictModel->find($id);
        ! $dict && paramError('字典不存在', 404);

        $dict->delete();

        CacheKeys::flush(CacheKeys::DICT_TREE, CacheKeys::dictItems($id));

        return true;
    }
}
