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

/**
 * 我的发送记录：只查当前登录用户发起的邮件。
 */
class GetMyRecordList
{
    public function __construct(
        protected EmailSendRecordModel $recordModel,
    ) {
    }

    public function handle(EmailRequest $request): mixed
    {
        return $this->recordModel
            ->where('create_by', (int) currentUser('id'))
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
