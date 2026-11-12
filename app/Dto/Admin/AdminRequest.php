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

namespace App\Dto\Admin;

use App\Dto\BaseRequestDto;

/**
 * 管理员模块请求参数.
 *
 * 对外字段与库内字段同名（phone 而非 mobile），避免多一层映射。
 */
class AdminRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $username = null;

    public ?string $password = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $avatar = null;

    public ?int $status = null;

    public ?int $is_admin = null;

    public ?array $role_ids = null;

    public ?int $admin_id = null;

    public ?string $new_password = null;

    public ?string $old_password = null;

    public ?int $session_id = null;

    public ?string $admin_code = null;

    public ?array $role_codes = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'username' => 'required|string|min:3|max:50',
                'password' => 'required|string|min:6|max:100',
                'name' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'avatar' => 'nullable|string|max:255',
                'status' => 'nullable|in:0,1',
                // 创建时允许传入，由 Action 显式拒绝，比静默丢弃更好排查
                'is_admin' => 'nullable|in:0,1',
                'role_ids' => 'nullable|array',
                'role_ids.*' => 'integer|min:1',
            ],
            'update' => [
                'username' => 'nullable|string|min:3|max:50',
                'password' => 'nullable|string|min:6|max:100',
                'name' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'avatar' => 'nullable|string|max:255',
                'status' => 'nullable|in:0,1',
                'is_admin' => 'nullable|in:0,1',
                'role_ids' => 'nullable|array',
                'role_ids.*' => 'integer|min:1',
            ],
            'list' => [
                ...$this->paginationRules(),
                'status' => 'nullable|in:0,1',
                'is_admin' => 'nullable|in:0,1',
            ],
            'resetPassword' => [
                'admin_id' => 'required|integer|min:1',
                'new_password' => 'required|string|min:6|max:100',
            ],
            'updateProfile' => [
                'name' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'avatar' => 'nullable|string|max:255',
            ],
            'changePassword' => [
                'old_password' => 'required|string|max:100',
                'new_password' => 'required|string|min:6|max:100',
            ],
            'forceLogout' => [
                'admin_id' => 'required|integer|min:1',
                'session_id' => 'nullable|integer|min:1',
            ],
            // 按管理员标识 + 角色标识分配（与 Role 模块按 ID 的接口区分）
            'assignRoles' => [
                'admin_code' => 'required|string|max:50',
                'role_codes' => 'required|array|min:1',
                'role_codes.*' => 'string|max:50',
            ],
            'revokeRole' => [
                'admin_id' => 'required|integer|min:1',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer|min:1',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id', 'role_ids', 'admin_id', 'new_password', 'old_password', 'session_id', 'admin_code', 'role_codes'];
    }
}
