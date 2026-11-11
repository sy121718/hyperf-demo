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
use App\Model\System\SysDictModel;

class Delete
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $dict = $this->dictModel->findOrNotFound($id, '字典');

        $dict->delete();

        CacheKeys::flush(CacheKeys::DICT_TREE, CacheKeys::dictItems($id));

        return true;
    }
}
