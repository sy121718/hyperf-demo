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
use App\Service\Role\Dto\CreateRoleDto;
use App\Service\Role\Dto\UpdateRoleDto;
use Hyperf\HttpServer\Router\Dispatched;

class RoleRequest extends BaseRequest
{
    public function rules(): array
    {
        $action = $this->getRouteAction();
        return match ($action) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'list' => array_merge($this->getPaginationRules(), $this->getSearchRules(), $this->getStatusRules()),
            default => [],
        };
    }

    protected function getDtoClass(): ?string
    {
        $action = $this->getRouteAction();
        return match ($action) {
            'create' => CreateRoleDto::class,
            'update' => UpdateRoleDto::class,
            default => null,
        };
    }

    private function getCreateRules(): array
    {
        return [
            'role_name' => 'required|string|max:50',
            'role_code' => 'required|string|max:50',
            'role_type' => 'nullable|string|in:1,2,3',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'remark' => 'nullable|string|max:200',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'integer|min:1',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|integer|min:1',
            'role_name' => 'nullable|string|max:50',
            'role_code' => 'nullable|string|max:50',
            'role_type' => 'nullable|string|in:1,2,3',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
            'remark' => 'nullable|string|max:200',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'integer|min:1',
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
        return '';
    }
}
