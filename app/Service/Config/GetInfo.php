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

use App\Model\System\SysConfigModel;

class GetInfo
{
    public function __construct(
        protected SysConfigModel $configModel,
    ) {
    }

    public function handle(int $id): array
    {
        requireId($id, '配置');

        $config = $this->configModel->find($id);
        ! $config && notFound('配置');

        return $config->toArray();
    }
}
