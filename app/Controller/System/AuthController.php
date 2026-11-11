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

namespace App\Controller\System;

use App\Controller\AbstractController;
use App\Dto\Admin\AdminRequest;
use App\Dto\Auth\LoginRequest;
use App\Service\Admin\Auth as AuthAction;
use App\Service\Admin\Profile as ProfileAction;

class AuthController extends AbstractController
{
    public function login(LoginRequest $request): array
    {
        $result = container()->get(AuthAction\Login::class)->handle($request);
        return $this->success($result, '登录成功');
    }

    public function logout(): array
    {
        container()->get(AuthAction\Logout::class)->handle();
        return $this->success(null, '退出登录成功');
    }

    public function profile(): array
    {
        $result = container()->get(ProfileAction\GetProfile::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function updateProfile(AdminRequest $request): array
    {
        container()->get(ProfileAction\UpdateProfile::class)->handle($request);
        return $this->success(null, '更新成功');
    }

    public function changePassword(AdminRequest $request): array
    {
        container()->get(ProfileAction\ChangePassword::class)->handle($request);
        return $this->success(null, '密码修改成功');
    }
}
