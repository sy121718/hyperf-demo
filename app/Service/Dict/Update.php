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

class Update
{
    public function __construct(
        protected SysDictModel $dictModel,
    ) {
    }

    public function handle(DictRequest $request): array
    {
        $id = (int) $request->id;
        $dict = $this->dictModel->findOrNotFound($id, '字典');

        if ($request->dict_code !== null && $request->dict_code !== $dict->dict_code) {
            $this->dictModel->where('dict_code', $request->dict_code)
                ->where('id', '!=', $id)->exists()
                && paramError('字典编码已存在');
        }

        $dict->update($request->toDbArrayFiltered());

        CacheKeys::flush(CacheKeys::DICT_TREE, CacheKeys::dictItems($id));

        return $dict->fresh()->toArray();
    }
}
