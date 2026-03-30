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

namespace App\Service\Admin\Auth;

use App\Model\Admin\SysAdminModel;
use App\Service\Ops\Session\CreateSession;
use Firebase\JWT\JWT;
use Hyperf\HttpServer\Contract\RequestInterface;

use function Hyperf\Support\env;

class Login
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected CreateSession $createSessionAction,
        protected RequestInterface $request,
    ) {
    }

    /**
     * 执行管理员登录.
     *
     * @return array{token: string, token_type: string, expires_in: int, admin_info: array}
     */
    public function handle(array $data): array
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        empty($username) && paramError('用户名不能为空');
        empty($password) && paramError('密码不能为空');

        $admin = $this->adminModel->where('username', $username)->first();
        ! $admin && paramError('账号或密码错误');
        $admin->status !== 1 && paramError('账号已被禁用');
        ! password_verify($password, $admin->password) && paramError('账号或密码错误');

        $ttl = (int) env('JWT_TTL', 7200);
        $now = time();
        $payload = [
            'sub' => $admin->id,
            'iat' => $now,
            'exp' => $now + $ttl,
            'username' => $admin->username,
        ];
        $secret = (string) env('JWT_SECRET', '');
        $algo = (string) env('JWT_ALGO', 'HS256');
        $token = JWT::encode($payload, $secret, $algo);

        $admin->last_login_time = millisecond();
        $admin->last_login_ip = $this->getClientIp();
        $admin->login_failure_count = 0;
        $admin->save();

        $this->createSessionAction->handle([
            'admin_id' => $admin->id,
            'token' => $token,
            'user_agent' => $this->request->getHeaderLine('User-Agent'),
            'ip' => $this->getClientIp(),
        ]);

        $adminInfo = $admin->toArray();
        unset($adminInfo['password']);

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl,
            'admin_info' => $adminInfo,
        ];
    }

    private function getClientIp(): string
    {
        $serverParams = $this->request->getServerParams();
        return $serverParams['remote_addr'] ?? '127.0.0.1';
    }
}
