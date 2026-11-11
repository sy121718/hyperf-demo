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

class NoticeModel extends BaseModel
{
    protected ?string $table = 'notice';

    protected array $fillable = [
        'title', 'content', 'notice_type', 'is_top', 'is_popup',
        'status', 'publish_time', 'expire_time', 'read_count',
        'create_by', 'update_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'notice_type' => 'integer',
        'is_top' => 'integer',
        'is_popup' => 'integer',
        'status' => 'integer',
        'read_count' => 'integer',
        'create_by' => 'integer',
        'update_by' => 'integer',
    ];

    protected array $whereableFields = [
        'notice_type', 'is_top', 'is_popup', 'status',
    ];

    protected array $searchableFields = [
        'title', 'content',
    ];

    protected array $orderableFields = [
        'id', 'title', 'notice_type', 'is_top',
        'read_count', 'status', 'publish_time', 'expire_time',
        'create_time',
    ];

    /**
     * 用户可见的有效公告：已发布且未过期。
     *
     * expire_time 为 NULL 表示不设过期时间（长期有效），不能因 NULL 被过滤掉。
     * 列表与未读数必须共用这一个口径，否则「看得到但不算未读」。
     */
    public function scopePublished($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('expire_time')
                    ->orWhere('expire_time', '>', date('Y-m-d H:i:s'));
            });
    }

    public function targets()
    {
        return $this->hasMany(NoticeTargetModel::class, 'notice_id', 'id');
    }

    public function readRecords()
    {
        return $this->hasMany(NoticeReadModel::class, 'notice_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(SysAdminModel::class, 'create_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(SysAdminModel::class, 'update_by', 'id');
    }
}
