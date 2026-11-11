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

namespace App\Model\Notify;

use App\Common\Model\BaseModel;

class EmailSendRecipientModel extends BaseModel
{
    /** 待发送 */
    public const STATUS_PENDING = 0;

    /** 发送成功 */
    public const STATUS_SUCCESS = 1;

    /** 发送失败 */
    public const STATUS_FAILED = 2;

    public bool $timestamps = true;

    protected ?string $table = 'email_send_recipient';

    protected array $fillable = [
        'record_id', 'email', 'name', 'status', 'error_message', 'sent_time',
    ];

    protected array $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
    ];

    protected bool $autoFillCreate = false;

    protected bool $autoFillUpdate = false;

    public function sendRecord()
    {
        return $this->belongsTo(EmailSendRecordModel::class, 'record_id', 'id');
    }
}
