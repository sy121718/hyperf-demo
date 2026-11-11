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

namespace App\Service\Notice;

use App\Model\Notify\NoticeModel;
use Hyperf\DbConnection\Db;

use function Hyperf\Coroutine\parallel;

class GetStatistics
{
    public function __construct(
        protected NoticeModel $noticeModel,
    ) {
    }

    public function handle(array $data): array
    {
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');

        $startTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        [$typeStats, $statusStats, $totalReadCount] = parallel([
            fn () => $this->noticeModel
                ->whereBetween('create_time', [$startTime, $endTime])
                ->select('notice_type', Db::raw('count(*) as count'))
                ->groupBy('notice_type')
                ->get()
                ->toArray(),

            fn () => $this->noticeModel
                ->whereBetween('create_time', [$startTime, $endTime])
                ->select('status', Db::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->toArray(),

            fn () => (int) $this->noticeModel
                ->whereBetween('create_time', [$startTime, $endTime])
                ->sum('read_count'),
        ]);

        return [
            'type_stats' => $typeStats,
            'status_stats' => $statusStats,
            'total_read_count' => $totalReadCount,
            'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
        ];
    }
}
