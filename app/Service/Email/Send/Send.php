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
use App\Model\Notify\EmailSendRecipientModel;
use App\Model\Notify\EmailSendRecordModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Send
{
    public function __construct(
        protected EmailSendRecordModel $recordModel,
        protected EmailSendRecipientModel $recipientModel,
    ) {
    }

    public function handle(EmailRequest $request): array
    {
        $toList = $request->recipients();
        empty($toList) && paramError('收件人不能为空');

        Db::beginTransaction();
        try {
            $dbData = $request->toDbArrayFiltered();
            $dbData['status'] = EmailSendRecordModel::STATUS_PENDING;
            $dbData['total_recipients'] = count($toList);
            $dbData['success_count'] = 0;
            $dbData['failed_count'] = 0;

            $record = $this->recordModel->create($dbData);

            // insert 不触发模型事件，时间戳需显式给出
            $now = date('Y-m-d H:i:s.v');
            $recipients = array_map(static fn (string $email) => [
                'record_id' => $record->id,
                'email' => $email,
                'status' => EmailSendRecipientModel::STATUS_PENDING,
                'create_time' => $now,
                'update_time' => $now,
            ], $toList);

            $this->recipientModel->insert($recipients);

            Db::commit();

            return [
                'record_id' => $record->id,
                'recipient_count' => count($toList),
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
