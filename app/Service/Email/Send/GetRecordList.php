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

use App\Dto\Email\EmailRequest;
use App\Model\Notify\EmailSendRecordModel;

class GetRecordList
{
    public function __construct(
        protected EmailSendRecordModel $recordModel,
    ) {
    }

    public function handle(EmailRequest $request): mixed
    {
        return $this->recordModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->recordModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
