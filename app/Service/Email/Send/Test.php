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

namespace App\Service\Email\Send;

class Test
{
    public function handle(array $data): bool
    {
        empty($data['to']) && paramError('收件人不能为空');

        return true;
    }
}
