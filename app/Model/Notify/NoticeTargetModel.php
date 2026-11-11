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

class NoticeTargetModel extends BaseModel
{
    public bool $timestamps = false;

    protected ?string $table = 'notice_target';

    protected bool $autoFillCreate = false;

    protected bool $autoFillUpdate = false;

    protected array $fillable = [
        'notice_id', 'target_type', 'target_id',
    ];

    protected array $casts = [
        'id' => 'integer',
        'notice_id' => 'integer',
        'target_type' => 'integer',
        'target_id' => 'integer',
    ];

    public function notice()
    {
        return $this->belongsTo(NoticeModel::class, 'notice_id', 'id');
    }
}
