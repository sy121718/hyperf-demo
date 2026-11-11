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

namespace App\Dto\Dict;

use App\Dto\BaseResponse;

/**
 * 字典模块响应字段白名单.
 *
 * 字典树/详情都带 items 子集合，统一在 detailFields / listFields 里放行。
 */
class DictResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return ['id', 'dict_name', 'dict_code', 'dict_type', 'status', 'create_time', 'items'];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'dict_name', 'dict_code', 'dict_type', 'status', 'create_time', 'items',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'dict_code', 'dict_name'];
    }
}
