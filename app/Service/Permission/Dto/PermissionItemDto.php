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

namespace App\Service\Permission\Dto;

use App\Common\Dto\BaseDto;

class PermissionItemDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'perm_code' => 'perm_code',
        'perm_name' => 'perm_name',
        'parent_id' => 'parent_id',
        'api_path' => 'api_path',
        'http_method' => 'http_method',
        'controller_method' => 'controller_method',
        'is_public' => 'is_public',
        'status' => 'status',
        'remark' => 'remark',
    ];

    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $permCode = null,
        public readonly ?string $permName = null,
        public readonly ?int $parentId = null,
        public readonly ?string $apiPath = null,
        public readonly ?string $httpMethod = null,
        public readonly ?string $controllerMethod = null,
        public readonly int $isPublic = 0,
        public readonly int $status = 1,
        public readonly ?string $remark = null,
    ) {
    }
}
