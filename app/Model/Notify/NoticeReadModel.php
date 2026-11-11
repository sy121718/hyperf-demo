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

class NoticeReadModel extends BaseModel
{
    public bool $timestamps = false;

    protected ?string $table = 'notice_read';

    protected bool $autoFillCreate = false;

    protected bool $autoFillUpdate = false;

    protected array $fillable = [
        'notice_id', 'user_id', 'read_time',
    ];

    protected array $casts = [
        'id' => 'integer',
        'notice_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function notice()
    {
        return $this->belongsTo(NoticeModel::class, 'notice_id', 'id');
    }

    public function admin()
    {
        return $this->belongsTo(SysAdminModel::class, 'user_id', 'id');
    }
}
