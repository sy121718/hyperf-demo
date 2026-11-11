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

namespace App\Service\Config;

use App\Dto\Config\ConfigRequest;
use App\Model\System\SysConfigModel;

class Create
{
    public function __construct(
        protected SysConfigModel $configModel,
    ) {
    }

    public function handle(ConfigRequest $request): array
    {
        $groupKey = (string) $request->group_key;

        $this->configModel->where('group_key', $groupKey)->exists()
            && paramError('配置分组键已存在');

        $dbData = $request->toDbArrayFiltered();
        $dbData['config_data'] ??= [];
        $dbData['status'] ??= 1;

        $config = $this->configModel->create($dbData);

        return $config->toArray();
    }
}
