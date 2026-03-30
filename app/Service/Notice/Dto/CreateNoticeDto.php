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

namespace App\Service\Notice\Dto;

use App\Common\Dto\BaseDto;

class CreateNoticeDto extends BaseDto
{
    protected static array $fields = [
        'title' => 'title',
        'content' => 'content',
        'notice_type' => 'notice_type',
        'is_top' => 'is_top',
        'is_popup' => 'is_popup',
        'publish_time' => 'publish_time',
        'expire_time' => 'expire_time',
        'status' => 'status',
        'create_by' => 'create_by',
    ];

    public function __construct(
        public readonly string $title,
        public readonly ?string $content = null,
        public readonly int $noticeType = 1,
        public readonly int $isTop = 0,
        public readonly int $isPopup = 0,
        public readonly ?string $publishTime = null,
        public readonly ?string $expireTime = null,
        public readonly int $status = 0,
        public readonly ?int $createBy = null,
    ) {
    }
}
