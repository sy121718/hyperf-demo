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

namespace App\Service\Permission;

use App\Common\CacheKeys;
use App\Dto\Permission\PermissionRequest;
use App\Model\Permission\SysPermissionDictModel;
use Hyperf\DbConnection\Db;
use Throwable;

class BatchCreate
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    /**
     * @param PermissionRequest[] $items
     */
    public function handle(array $items): array
    {
        empty($items) && paramError('权限数据不能为空');

        $created = [];
        $skippedCodes = [];

        Db::beginTransaction();
        try {
            foreach ($items as $item) {
                $dbData = $item->toDbArrayFiltered();

                // 已存在的编码跳过，不覆盖
                if ($this->permissionModel->where('perm_code', $dbData['perm_code'])->exists()) {
                    $skippedCodes[] = $dbData['perm_code'];
                    continue;
                }

                $created[] = $this->permissionModel->create($dbData);
            }

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API);

            return [
                'created' => $created,
                'skipped_codes' => $skippedCodes,
                'created_count' => count($created),
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
