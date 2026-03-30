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

namespace App\Service\Menu\Dto;

use App\Common\Dto\BaseDto;

class CreateMenuDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'menu_code' => 'menu_code',
        'menu_name' => 'menu_name',
        'parent_id' => 'parent_id',
        'type' => 'type',
        'title' => 'title',
        'path' => 'path',
        'component' => 'component',
        'external_url' => 'external_url',
        'icon' => 'icon',
        'status' => 'status',
        'is_hidden' => 'is_hidden',
        'is_public' => 'is_public',
        'sort_order' => 'sort_order',
        'permission_codes' => 'permission_codes',
    ];

    public function __construct(
        public readonly string $menuCode,
        public readonly string $menuName,
        public readonly ?int $parentId = null,
        public readonly ?int $type = null,
        public readonly ?string $title = null,
        public readonly ?string $path = null,
        public readonly ?string $component = null,
        public readonly ?string $externalUrl = null,
        public readonly ?string $icon = null,
        public readonly int $status = 1,
        public readonly int $isHidden = 0,
        public readonly int $isPublic = 0,
        public readonly ?int $sortOrder = null,
        public readonly array $permissionCodes = [],
    ) {
    }
}
