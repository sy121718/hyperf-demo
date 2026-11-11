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
use App\Model\Admin\SysAdminModel;

class EmailSendRecordModel extends BaseModel
{
    /** 待发送 */
    public const STATUS_PENDING = 0;

    /** 发送中 */
    public const STATUS_SENDING = 1;

    /** 全部发送成功 */
    public const STATUS_SUCCESS = 2;

    /** 全部发送失败 */
    public const STATUS_FAILED = 3;

    protected ?string $table = 'email_send_record';

    protected array $fillable = [
        'template_id', 'subject', 'content', 'status',
        'total_recipients', 'success_count', 'failed_count',
        'error_message', 'sent_time', 'create_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'template_id' => 'integer',
        'status' => 'integer',
        'total_recipients' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'create_by' => 'integer',
    ];

    protected array $whereableFields = [
        'template_id', 'status',
    ];

    protected array $searchableFields = [
        'subject',
    ];

    protected array $orderableFields = [
        'id', 'status', 'sent_time', 'total_recipients', 'success_count', 'create_time',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplateModel::class, 'template_id', 'id');
    }

    public function recipients()
    {
        return $this->hasMany(EmailSendRecipientModel::class, 'record_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(SysAdminModel::class, 'create_by', 'id');
    }
}
