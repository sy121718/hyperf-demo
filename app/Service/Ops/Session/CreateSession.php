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

namespace App\Service\Ops\Session;

use App\Model\Admin\SysAdminSessionsModel;
use DateTimeImmutable;
use DateTimeZone;

use function Hyperf\Support\env;

class CreateSession
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    /**
     * 创建管理员登录会话.
     *
     * @param array $data 包含 admin_id, token, user_agent, ip
     */
    public function handle(array $data): void
    {
        $adminId = (int) ($data['admin_id'] ?? 0);
        $token = $data['token'] ?? '';
        $userAgent = $data['user_agent'] ?? '';
        $ip = $data['ip'] ?? '';

        $ttl = (int) env('JWT_TTL', 7200);
        $now = millisecond();

        $this->sessionModel->create([
            'admin_id' => $adminId,
            'device_type' => $this->detectDeviceType($userAgent),
            'device_info' => mb_substr($userAgent, 0, 200),
            'jwt_token' => $token,
            'login_ip' => $ip,
            'login_time' => $now,
            'last_active_time' => $now,
            'expires_time' => (new DateTimeImmutable("+{$ttl} seconds", new DateTimeZone('UTC')))
                ->format('Y-m-d H:i:s.v'),
            'status' => 1,
            'create_by' => $adminId,
        ]);
    }

    private function detectDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        return 'desktop';
    }
}
