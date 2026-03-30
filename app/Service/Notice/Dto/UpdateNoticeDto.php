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

class UpdateNoticeDto
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $title = null,
        public readonly ?string $content = null,
        public readonly ?int $noticeType = null,
        public readonly ?int $isTop = null,
        public readonly ?int $isPopup = null,
        public readonly ?string $publishTime = null,
        public readonly mixed $expireTime = null,
        public readonly ?int $status = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: (int) $data['id'],
            title: $data['title'] ?? null,
            content: $data['content'] ?? null,
            noticeType: isset($data['notice_type']) ? (int) $data['notice_type'] : null,
            isTop: isset($data['is_top']) ? (int) $data['is_top'] : null,
            isPopup: isset($data['is_popup']) ? (int) $data['is_popup'] : null,
            publishTime: $data['publish_time'] ?? null,
            expireTime: $data['expire_time'] ?? null,
            status: isset($data['status']) ? (int) $data['status'] : null,
        );
    }

    public function toUpdateArray(): array
    {
        $data = [];

        $this->title !== null && $data['title'] = $this->title;
        $this->content !== null && $data['content'] = $this->content;
        $this->noticeType !== null && $data['notice_type'] = $this->noticeType;
        $this->isTop !== null && $data['is_top'] = $this->isTop;
        $this->isPopup !== null && $data['is_popup'] = $this->isPopup;
        $this->publishTime !== null && $data['publish_time'] = $this->publishTime;
        $this->expireTime !== null && $data['expire_time'] = self::convertExpireTime($this->expireTime);
        $this->status !== null && $data['status'] = $this->status;

        return $data;
    }

    public function getPublishedUpdateFields(): array
    {
        $data = [];

        $this->isTop !== null && $data['is_top'] = $this->isTop;
        $this->isPopup !== null && $data['is_popup'] = $this->isPopup;
        $this->expireTime !== null && $data['expire_time'] = self::convertExpireTime($this->expireTime);

        return $data;
    }

    private static function convertExpireTime(mixed $expireTime): ?string
    {
        if ($expireTime === null || $expireTime === '') {
            return null;
        }

        if (! is_numeric($expireTime) && strtotime((string) $expireTime) !== false) {
            return (string) $expireTime;
        }

        if (is_numeric($expireTime) && $expireTime > 1000000000) {
            $seconds = $expireTime > 1000000000000 ? $expireTime / 1000 : $expireTime;
            return date('Y-m-d H:i:s', (int) $seconds);
        }

        return null;
    }
}
