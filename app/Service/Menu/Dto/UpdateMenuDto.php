<?php

declare(strict_types=1);

namespace App\Service\Menu\Dto;

use App\Common\Dto\BaseDto;

class UpdateMenuDto extends BaseDto
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
        public readonly int $id,
        public readonly ?string $menuCode = null,
        public readonly ?string $menuName = null,
        public readonly ?int $parentId = null,
        public readonly ?int $type = null,
        public readonly ?string $title = null,
        public readonly ?string $path = null,
        public readonly ?string $component = null,
        public readonly ?string $externalUrl = null,
        public readonly ?string $icon = null,
        public readonly ?int $status = null,
        public readonly ?int $isHidden = null,
        public readonly ?int $isPublic = null,
        public readonly ?int $sortOrder = null,
        public readonly array $permissionCodes = [],
    ) {}
}
