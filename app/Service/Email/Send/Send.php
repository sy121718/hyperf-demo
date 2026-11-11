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

    public function handle(array $data): array
    {
        empty($data['subject']) && paramError('邮件主题不能为空');
        empty($data['content']) && paramError('邮件内容不能为空');

        $toList = $data['to_list'] ?? [];
        if (! empty($data['to'])) {
            $toList = [$data['to']];
        }
        empty($toList) && paramError('收件人不能为空');

        Db::beginTransaction();
        try {
            $record = $this->recordModel->create([
                'template_id' => $data['template_id'] ?? null,
                'subject' => $data['subject'],
                'content' => $data['content'],
                'status' => 'pending',
                'total_recipients' => count($toList),
                'success_count' => 0,
                'failed_count' => 0,
            ]);

            $recipients = [];
            foreach ($toList as $email) {
                $recipients[] = [
                    'record_id' => $record->id,
                    'email' => $email,
                    'status' => 'pending',
                    'create_time' => date('Y-m-d H:i:s.v'),
                    'update_time' => date('Y-m-d H:i:s.v'),
                ];
            }

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
