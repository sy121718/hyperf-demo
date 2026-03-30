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

use App\Model\Config\SysConfigModel;

class GetInfo
{
    public function __construct(
        protected SysConfigModel $configModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('配置ID不能为空');

        $config = $this->configModel->find($id);
        ! $config && paramError('配置不存在', 404);

        return $config->toArray();
    }
}
