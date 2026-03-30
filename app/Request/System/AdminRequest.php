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

namespace App\Request\System;

use App\Request\BaseRequest;
use App\Service\Admin\Manage\Dto\CreateAdminDto;
use App\Service\Admin\Manage\Dto\UpdateAdminDto;
use Hyperf\HttpServer\Router\Dispatched;

class AdminRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->getRouteAction();

        return match ($action) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'list' => $this->getListRules(),
            'delete', 'info' => $this->getIdRules(),
            'resetPassword' => $this->getResetPasswordRules(),
            default => [],
        };
    }

    protected function getDtoClass(): ?string
    {
        $action = $this->getRouteAction();

        return match ($action) {
            'create' => CreateAdminDto::class,
            'update' => UpdateAdminDto::class,
            default => null,
        };
    }

    private function getCreateRules(): array
    {
        return [
            'username' => 'required|string|min:3|max:50',
            'password' => 'required|string|min:6|max:100',
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|min:1',
            'status' => 'nullable|in:0,1',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|integer|min:1',
            'username' => 'nullable|string|min:3|max:50',
            'password' => 'nullable|string|min:6|max:100',
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|min:1',
            'status' => 'nullable|in:0,1',
            'isAdmin' => 'nullable|in:0,1',
        ];
    }

    private function getListRules(): array
    {
        return array_merge(
            $this->getPaginationRules(),
            $this->getSearchRules(),
            $this->getStatusRules(),
        );
    }

    private function getIdRules(): array
    {
        return [
            'id' => 'required|integer|min:1',
        ];
    }

    private function getResetPasswordRules(): array
    {
        return [
            'admin_id' => 'required|integer|min:1',
            'new_password' => 'required|string|min:6|max:100',
        ];
    }

    private function getRouteAction(): string
    {
        $dispatched = $this->getAttribute(Dispatched::class);
        if (! $dispatched || ! $dispatched->handler) {
            return '';
        }
        $callback = $dispatched->handler->callback;
        if (is_string($callback) && str_contains($callback, '@')) {
            return explode('@', $callback)[1] ?? '';
        }
        if (is_array($callback) && isset($callback[1])) {
            return $callback[1];
        }
        return '';
    }
}
