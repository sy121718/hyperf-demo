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

namespace App\Service\Email\Template;

use App\Model\Email\EmailTemplateModel;

class GetList
{
    public function __construct(
        protected EmailTemplateModel $templateModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'template_code', 'template_name', 'subject', 'template_type', 'status', 'create_time',
        ];

        $query = $this->templateModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->templateModel->getSearchableFields(), 'like', "%{$search}%"))
            ->sort($data);

        // 条件加载关联
        if ($withDetail) {
            $query->with(['creator', 'updater']);
        }

        return $query->paginate((int) ($data['limit'] ?? 10), $fields, 'page', (int) ($data['page'] ?? 1));
    }
}
