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

use App\Model\Notify\EmailSendRecordModel;

class GetRecordList
{
    public function __construct(
        protected EmailSendRecordModel $recordModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;

        return $this->recordModel
            ->with(['creator', 'template'])
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->recordModel->getSearchableFields(), 'like', "%{$search}%"))
            ->sort($data)
            ->paginate((int) ($data['limit'] ?? 10), ['*'], 'page', (int) ($data['page'] ?? 1));
    }
}
