# RBAC Code 简化重构实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 将 RBAC 权限系统的 ID 关联改为 code 关联，简化表结构，去掉中间表

**Architecture:**
- 菜单直接存储 `permission_code`（目录除外）
- 角色直接存储 `permission_codes`（JSON 数组）
- 管理员关联角色用 `admin_code + role_code`（替代 ID 关联）
- 删除 `sys_menu_permission` 和 `sys_role_menu` 两张中间表
- 鉴权时直接通过 `role.permission_codes` 判断

**Tech Stack:** Hyperf 3.1, MySQL, PHP 8.3

---

## 文件变更总览

### 数据库变更
| 操作 | 表名 | 变更内容 |
|------|------|----------|
| Modify | `sys_menus` | 添加 `permission_code` varchar(100) |
| Modify | `sys_role` | 添加 `permission_codes` json |
| Modify | `sys_admin` | 添加 `admin_code` varchar(50) unique |
| Modify | `sys_admin_role` | `admin_id` → `admin_code`, `role_id` → `role_code` |
| Delete | `sys_menu_permission` | 删除整表 |
| Delete | `sys_role_menu` | 删除整表 |

### Model 变更
| 文件 | 变更 |
|------|------|
| `app/Model/Menu/SysMenuModel.php` | 添加 `permission_code` fillable，移除 `permissions()` 关联 |
| `app/Model/Role/SysRoleModel.php` | 添加 `permission_codes` json cast，移除 `menus()` 关联 |
| `app/Model/Admin/SysAdminModel.php` | 添加 `admin_code` fillable + unique |
| `app/Model/Admin/SysAdminRoleModel.php` | 字段改为 `admin_code`, `role_code` |
| `app/Model/Menu/SysMenuPermissionModel.php` | 删除文件 |
| `app/Model/Role/SysRoleMenuModel.php` | 删除文件 |

### Service 变更
| 文件 | 变更 |
|------|------|
| `app/Service/Menu/AssignPermissions.php` | 删除 |
| `app/Service/Menu/RemovePermission.php` | 删除 |
| `app/Service/Menu/Create.php` | 添加 `permission_code` 写入逻辑 |
| `app/Service/Menu/Update.php` | 添加 `permission_code` 更新逻辑 |
| `app/Service/Menu/Dto/CreateMenuDto.php` | `permission_ids` → `permission_codes` |
| `app/Service/Menu/Dto/UpdateMenuDto.php` | `permission_ids` → `permission_codes` |
| `app/Service/Role/AssignMenus.php` | 删除 |
| `app/Service/Role/RemoveMenu.php` | 删除 |
| `app/Service/Role/AssignPermissions.php` | 新建，角色分配权限 |
| `app/Service/Role/Create.php` | 处理 `permission_codes` 替代 `menu_ids` |
| `app/Service/Role/Update.php` | 处理 `permission_codes` 替代 `menu_ids` |
| `app/Service/Role/Dto/CreateRoleDto.php` | `menu_ids` → `permission_codes` |
| `app/Service/Role/Dto/UpdateRoleDto.php` | `menu_ids` → `permission_codes` |
| `app/Service/Permission/CheckUserPermission.php` | 简化，直接从 role.permission_codes 鉴权 |
| `app/Service/Admin/Permission/AssignRoles.php` | 使用 `admin_code` 关联 |
| `app/Service/Admin/Permission/GetPermissions.php` | 返回 `permission_codes` |

### Controller 变更
| 文件 | 变更 |
|------|------|
| `app/Controller/System/MenuController.php` | 删除 `assignPermissions`, `removePermission` |
| `app/Controller/System/RoleController.php` | `assignMenus` → `assignPermissions`, `removeMenu` → 删除 |

### Middleware 变更
| 文件 | 变更 |
|------|------|
| `app/Middleware/PermissionMiddleware.php` | 简化查询，直接通过 role.permission_codes 判断 |

---

## Task 1: 数据库迁移

**Files:**
- SQL Migration File: `migrations/2026_03_30_000001_simplify_rbac_code.sql`

- [ ] **Step 1: 创建数据库迁移 SQL**

```sql
-- 1. sys_menus 表：添加 permission_code 字段
ALTER TABLE sys_menus
ADD COLUMN permission_code VARCHAR(100) DEFAULT NULL AFTER is_public;

-- 2. sys_role 表：添加 permission_codes 字段
ALTER TABLE sys_role
ADD COLUMN permission_codes JSON DEFAULT NULL AFTER remark;

-- 3. sys_admin 表：添加 admin_code 字段
ALTER TABLE sys_admin
ADD COLUMN admin_code VARCHAR(50) DEFAULT NULL AFTER username,
ADD UNIQUE INDEX idx_admin_code (admin_code);

-- 4. 初始化 admin_code（基于现有 username）
UPDATE sys_admin SET admin_code = username WHERE admin_code IS NULL;

-- 5. sys_admin_role 表：修改字段
ALTER TABLE sys_admin_role
DROP FOREIGN KEY sys_admin_role_ibfk_1,
DROP FOREIGN KEY sys_admin_role_ibfk_2;

ALTER TABLE sys_admin_role
DROP COLUMN admin_id,
DROP COLUMN role_id,
ADD COLUMN admin_code VARCHAR(50) NOT NULL AFTER id,
ADD COLUMN role_code VARCHAR(50) NOT NULL AFTER admin_code,
ADD INDEX idx_admin_code (admin_code),
ADD INDEX idx_role_code (role_code);

-- 6. 删除中间表
DROP TABLE IF EXISTS sys_menu_permission;
DROP TABLE IF EXISTS sys_role_menu;
```

- [ ] **Step 2: 执行数据库迁移**

```bash
mysql -h 127.0.0.1 -P 3306 -u root -proot base < migrations/2026_03_30_000001_simplify_rbac_code.sql
```

---

## Task 2: Model 变更 - SysMenuModel

**Files:**
- Modify: `app/Model/Menu/SysMenuModel.php:18-57`

- [ ] **Step 1: 更新 SysMenuModel**

```php
// 1. 添加 permission_code 到 fillable
protected array $fillable = [
    'menu_code', 'menu_name', 'parent_id', 'type', 'title',
    'path', 'component', 'external_url', 'icon', 'status',
    'is_hidden', 'is_public', 'permission_code', 'sort_order',
    'create_by', 'update_by',
];

// 2. 添加 casts
protected array $casts = [
    'id' => 'integer',
    'parent_id' => 'integer',
    'type' => 'integer',
    'status' => 'integer',
    'is_hidden' => 'integer',
    'is_public' => 'integer',
    'sort_order' => 'integer',
    'create_by' => 'integer',
    'update_by' => 'integer',
];

// 3. 移除 permissions() 关联方法（保留 children, parent, roles）
```

- [ ] **Step 2: 提交**

```bash
git add app/Model/Menu/SysMenuModel.php
git commit -m "refactor: add permission_code to SysMenuModel"
```

---

## Task 3: Model 变更 - SysRoleModel

**Files:**
- Modify: `app/Model/Role/SysRoleModel.php:18-62`

- [ ] **Step 1: 更新 SysRoleModel**

```php
// 1. 添加 permission_codes 到 fillable
protected array $fillable = [
    'role_name', 'role_code', 'role_type', 'sort_order',
    'status', 'remark', 'permission_codes', 'create_by', 'update_by',
];

// 2. 添加 casts（新增 permission_codes json cast）
protected array $casts = [
    'id' => 'integer',
    'sort_order' => 'integer',
    'status' => 'integer',
    'create_by' => 'integer',
    'update_by' => 'integer',
    'permission_codes' => 'array',  // JSON 存储的 permission_code 数组
];

// 3. 移除 menus() 关联方法（保留 admins()）
```

- [ ] **Step 2: 提交**

```bash
git add app/Model/Role/SysRoleModel.php
git commit -m "refactor: add permission_codes to SysRoleModel, remove menus relation"
```

---

## Task 4: Model 变更 - SysAdminModel

**Files:**
- Modify: `app/Model/Admin/SysAdminModel.php:18-63`

- [ ] **Step 1: 更新 SysAdminModel**

```php
// 1. 添加 admin_code 到 fillable
protected array $fillable = [
    'username', 'admin_code', 'password', 'name', 'avatar', 'email', 'phone',
    'status', 'is_admin', 'login_failure_count', 'locked_until_time',
    'last_failure_time', 'register_ip', 'register_location',
    'last_login_ip', 'last_login_location', 'last_login_isp',
    'last_login_time', 'create_by', 'update_by',
];

// 2. 添加 admin_code 到 whereableFields
protected array $whereableFields = [
    'username', 'admin_code', 'status', 'is_admin', 'email', 'phone',
];
```

- [ ] **Step 2: 提交**

```bash
git add app/Model/Admin/SysAdminModel.php
git commit -m "refactor: add admin_code to SysAdminModel"
```

---

## Task 5: Model 变更 - SysAdminRoleModel

**Files:**
- Modify: `app/Model/Admin/SysAdminRoleModel.php:9-33`

- [ ] **Step 1: 更新 SysAdminRoleModel**

```php
protected array $fillable = [
    'admin_code', 'role_code', 'create_by', 'update_by',
];

protected array $casts = [
    'id' => 'integer',
];

// 3. 修改关联方法
public function admin()
{
    return $this->belongsTo(SysAdminModel::class, 'admin_code', 'admin_code');
}

public function role()
{
    return $this->belongsTo(\App\Model\Role\SysRoleModel::class, 'role_code', 'role_code');
}
```

- [ ] **Step 2: 提交**

```bash
git add app/Model/Admin/SysAdminRoleModel.php
git commit -m "refactor: change SysAdminRoleModel to use code fields"
```

---

## Task 6: 删除中间表 Model 文件

**Files:**
- Delete: `app/Model/Menu/SysMenuPermissionModel.php`
- Delete: `app/Model/Role/SysRoleMenuModel.php`

- [ ] **Step 1: 删除文件**

```bash
rm app/Model/Menu/SysMenuPermissionModel.php
rm app/Model/Role/SysRoleMenuModel.php
git add app/Model/Menu/SysMenuPermissionModel.php app/Model/Role/SysRoleMenuModel.php
git commit -m "refactor: remove intermediate tables sys_menu_permission and sys_role_menu"
```

---

## Task 7: Menu DTO 变更

**Files:**
- Modify: `app/Service/Menu/Dto/CreateMenuDto.php:11-44`
- Modify: `app/Service/Menu/Dto/UpdateMenuDto.php:11-46`

- [ ] **Step 1: 更新 CreateMenuDto**

```php
// 将 permission_ids 改为 permission_codes
protected static array $fields = [
    'id' => 'id',
    'menu_code' => 'menu_code',
    'menu_name' => 'menu_name',
    'parent_id' => 'parent_id',
    'type' => 'type',
    'title' => 'title',
    'path' => 'path',
    'component' => 'component',
    'external_url' => 'external_url',
    'icon' => 'icon',
    'status' => 'status',
    'is_hidden' => 'is_hidden',
    'is_public' => 'is_public',
    'sort_order' => 'sort_order',
    'permission_codes' => 'permission_codes',  // 改为 permission_codes
];

public function __construct(
    public readonly string $menuCode,
    public readonly string $menuName,
    public readonly ?int $parentId = null,
    public readonly ?int $type = null,
    public readonly ?string $title = null,
    public readonly ?string $path = null,
    public readonly ?string $component = null,
    public readonly ?string $externalUrl = null,
    public readonly ?string $icon = null,
    public readonly int $status = 1,
    public readonly int $isHidden = 0,
    public readonly int $isPublic = 0,
    public readonly ?int $sortOrder = null,
    public readonly array $permissionCodes = [],  // 改为 array
) {}
```

- [ ] **Step 2: 更新 UpdateMenuDto**

```php
// 将 permission_ids 改为 permission_codes
protected static array $fields = [
    'id' => 'id',
    'menu_code' => 'menu_code',
    'menu_name' => 'menu_name',
    'parent_id' => 'parent_id',
    'type' => 'type',
    'title' => 'title',
    'path' => 'path',
    'component' => 'component',
    'external_url' => 'external_url',
    'icon' => 'icon',
    'status' => 'status',
    'is_hidden' => 'is_hidden',
    'is_public' => 'is_public',
    'sort_order' => 'sort_order',
    'permission_codes' => 'permission_codes',  // 改为 permission_codes
];

public function __construct(
    public readonly int $id,
    public readonly ?string $menuCode = null,
    public readonly ?string $menuName = null,
    public readonly ?int $parentId = null,
    public readonly ?int $type = null,
    public readonly ?string $title = null,
    public readonly ?string $path = null,
    public readonly ?string $component = null,
    public readonly ?string $externalUrl = null,
    public readonly ?string $icon = null,
    public readonly ?int $status = null,
    public readonly ?int $isHidden = null,
    public readonly ?int $isPublic = null,
    public readonly ?int $sortOrder = null,
    public readonly array $permissionCodes = [],  // 改为 array
) {}
```

- [ ] **Step 3: 提交**

```bash
git add app/Service/Menu/Dto/CreateMenuDto.php app/Service/Menu/Dto/UpdateMenuDto.php
git commit -m "refactor: change menu DTOs from permission_ids to permission_codes"
```

---

## Task 8: Menu Service 变更

**Files:**
- Modify: `app/Service/Menu/Create.php`
- Modify: `app/Service/Menu/Update.php`
- Delete: `app/Service/Menu/AssignPermissions.php`
- Delete: `app/Service/Menu/RemovePermission.php`

- [ ] **Step 1: 更新 Menu/Create.php**

```php
public function handle(CreateMenuDto $dto): array
{
    !$this->menuModel->isFieldUnique(['menu_code' => $dto->menuCode])
        && paramError("菜单标识 {$dto->menuCode} 已存在");

    // 目录类型不能有 permission_code
    $isDirectory = in_array($dto->type, [0, '0', 'directory']);
    if ($isDirectory && !empty($dto->permissionCodes)) {
        paramError('目录类型菜单不能分配权限');
    }

    // 非目录类型必须提供 permission_code
    if (!$isDirectory && empty($dto->permissionCodes)) {
        paramError('非目录类型菜单必须分配至少一个权限');
    }

    $dbData = $dto->toDbArray();
    unset($dbData['id']);
    $dbData['create_by'] = (int) currentUser('id');

    $menu = $this->menuModel->create($dbData);

    return CreateMenuDto::dbToFront($menu->toArray());
}
```

- [ ] **Step 2: 更新 Menu/Update.php**

```php
public function handle(UpdateMenuDto $dto): array
{
    $menu = $this->menuModel->find($dto->id);
    !$menu && paramError('菜单不存在', 404);

    if ($dto->menuCode && $dto->menuCode !== $menu->menu_code) {
        !$this->menuModel->isFieldUnique(['menu_code' => $dto->menuCode], $dto->id)
            && paramError("菜单标识 {$dto->menuCode} 已存在");
    }

    $updateData = $dto->toDbArrayFiltered();
    unset($updateData['id']);

    // 目录类型不能有 permission_code
    $isDirectory = in_array($updateData['type'] ?? $menu->type, [0, '0', 'directory']);
    if ($isDirectory && !empty($updateData['permission_codes'])) {
        paramError('目录类型菜单不能分配权限');
    }

    $menu->update($updateData);

    return UpdateMenuDto::dbToFront($menu->fresh()->toArray());
}
```

- [ ] **Step 3: 删除 AssignPermissions.php 和 RemovePermission.php**

```bash
rm app/Service/Menu/AssignPermissions.php app/Service/Menu/RemovePermission.php
git add app/Service/Menu/AssignPermissions.php app/Service/Menu/RemovePermission.php
git commit -m "refactor: remove menu permission assignment actions (no longer needed)"
```

- [ ] **Step 4: 提交 Menu/Create.php 和 Menu/Update.php**

```bash
git add app/Service/Menu/Create.php app/Service/Menu/Update.php
git commit -m "refactor: update Menu Create/Update to handle permission_codes"
```

---

## Task 9: Role DTO 变更

**Files:**
- Modify: `app/Service/Role/Dto/CreateRoleDto.php`
- Modify: `app/Service/Role/Dto/UpdateRoleDto.php`

- [ ] **Step 1: 更新 CreateRoleDto**

```php
protected static array $fields = [
    'id' => 'id',
    'role_code' => 'role_code',
    'role_name' => 'role_name',
    'role_type' => 'role_type',
    'sort_order' => 'sort_order',
    'status' => 'status',
    'remark' => 'remark',
    'permission_codes' => 'permission_codes',  // 改为 permission_codes
];

public function __construct(
    public readonly string $roleCode,
    public readonly string $roleName,
    public readonly string $roleType = '1',
    public readonly ?int $sortOrder = null,
    public readonly int $status = 1,
    public readonly ?string $remark = null,
    public readonly array $permissionCodes = [],  // 改为 permissionCodes
) {}
```

- [ ] **Step 2: 更新 UpdateRoleDto**

```php
protected static array $fields = [
    'id' => 'id',
    'role_code' => 'role_code',
    'role_name' => 'role_name',
    'role_type' => 'role_type',
    'sort_order' => 'sort_order',
    'status' => 'status',
    'remark' => 'remark',
    'permission_codes' => 'permission_codes',  // 改为 permission_codes
];

public function __construct(
    public readonly int $id,
    public readonly ?string $roleCode = null,
    public readonly ?string $roleName = null,
    public readonly ?string $roleType = null,
    public readonly ?int $sortOrder = null,
    public readonly ?int $status = null,
    public readonly ?string $remark = null,
    public readonly array $permissionCodes = [],  // 改为 permissionCodes
) {}
```

- [ ] **Step 3: 提交**

```bash
git add app/Service/Role/Dto/CreateRoleDto.php app/Service/Role/Dto/UpdateRoleDto.php
git commit -m "refactor: change role DTOs from menu_ids to permission_codes"
```

---

## Task 10: Role Service 变更

**Files:**
- Modify: `app/Service/Role/Create.php`
- Modify: `app/Service/Role/Update.php`
- Create: `app/Service/Role/AssignPermissions.php`
- Delete: `app/Service/Role/AssignMenus.php`
- Delete: `app/Service/Role/RemoveMenu.php`

- [ ] **Step 1: 更新 Role/Create.php**

```php
public function handle(CreateRoleDto $dto): array
{
    !$this->roleModel->isFieldUnique(['role_code' => $dto->roleCode])
        && paramError("角色标识 {$dto->roleCode} 已存在");

    $dbData = $dto->toDbArray();
    unset($dbData['id'], $dbData['permission_codes']);
    $dbData['create_by'] = (int) currentUser('id');
    $dbData['permission_codes'] = json_encode($dto->permissionCodes ?: []);

    $role = $this->roleModel->create($dbData);

    return CreateRoleDto::dbToFront($role->toArray());
}
```

- [ ] **Step 2: 更新 Role/Update.php**

```php
public function handle(UpdateRoleDto $dto): array
{
    $role = $this->roleModel->find($dto->id);
    !$role && paramError('角色不存在', 404);

    if ($dto->roleCode && $dto->roleCode !== $role->role_code) {
        !$this->roleModel->isFieldUnique(['role_code' => $dto->roleCode], $dto->id)
            && paramError("角色标识 {$dto->roleCode} 已存在");
    }

    $updateData = $dto->toDbArrayFiltered();
    unset($updateData['id']);

    if (isset($updateData['permission_codes'])) {
        $updateData['permission_codes'] = json_encode($updateData['permission_codes'] ?: []);
    }

    $role->update($updateData);

    return UpdateRoleDto::dbToFront($role->fresh()->toArray());
}
```

- [ ] **Step 3: 创建 Role/AssignPermissions.php**

```php
<?php

declare(strict_types=1);

namespace App\Service\Role;

use App\Common\CacheKeys;
use App\Model\Permission\SysPermissionDictModel;
use App\Model\Role\SysRoleModel;
use Hyperf\DbConnection\Db;

class AssignPermissions
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected SysPermissionDictModel $permissionModel,
    ) {}

    public function handle(string $roleCode, array $permissionCodes): array
    {
        !empty($roleCode) || paramError('角色标识不能为空');

        $role = $this->roleModel->where('role_code', $roleCode)->first();
        !$role && paramError('角色不存在', 404);

        // 验证权限 code 是否存在
        if (!empty($permissionCodes)) {
            $exists = $this->permissionModel
                ->whereIn('perm_code', $permissionCodes)
                ->count();
            $exists === count($permissionCodes) || paramError('部分权限不存在');
        }

        $role->update(['permission_codes' => json_encode($permissionCodes ?: [])]);

        CacheKeys::flush(
            CacheKeys::ROLE_OPTIONS,
            CacheKeys::ROLE_ALL,
            CacheKeys::PERM_USER,
            CacheKeys::MENU_USER,
            CacheKeys::MENU_SUPER
        );

        return ['role_code' => $roleCode, 'permission_codes' => $permissionCodes];
    }
}
```

- [ ] **Step 4: 删除 AssignMenus.php 和 RemoveMenu.php**

```bash
rm app/Service/Role/AssignMenus.php app/Service/Role/RemoveMenu.php
git add app/Service/Role/AssignMenus.php app/Service/Role/RemoveMenu.php
git commit -m "refactor: remove role-menu assignment actions"
```

- [ ] **Step 5: 提交 Create.php, Update.php, AssignPermissions.php**

```bash
git add app/Service/Role/Create.php app/Service/Role/Update.php app/Service/Role/AssignPermissions.php
git commit -m "refactor: update Role actions to handle permission_codes directly"
```

---

## Task 11: 鉴权 Service 变更 - CheckUserPermission

**Files:**
- Modify: `app/Service/Permission/CheckUserPermission.php`

- [ ] **Step 1: 重写 CheckUserPermission**

```php
<?php

declare(strict_types=1);

namespace App\Service\Permission;

use App\Model\Admin\SysAdminRoleModel;
use App\Model\Permission\SysPermissionDictModel;
use App\Model\Role\SysRoleModel;

class CheckUserPermission
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
        protected SysAdminRoleModel $adminRoleModel,
    ) {}

    public function handle(int $userId, string $apiPath, string $httpMethod): bool
    {
        $permission = $this->findPermission($apiPath, $httpMethod);

        if (!$permission) {
            return true;  // 权限未定义，默认放行
        }

        if ($permission->status !== 1) {
            return false;  // 权限已禁用
        }

        if ($permission->is_public === 1) {
            return true;  // 公开权限
        }

        // 获取用户的角色 permission_codes
        $permissionCodes = $this->getUserPermissionCodes($userId);

        if (empty($permissionCodes)) {
            return false;
        }

        // 直接检查 permission_code 是否在用户权限列表中
        return in_array($permission->perm_code, $permissionCodes);
    }

    protected function getUserPermissionCodes(int $userId): array
    {
        $roleCodes = $this->adminRoleModel
            ->where('admin_code', function ($q) use ($userId) {
                $q->select('admin_code')
                    ->from('sys_admin')
                    ->where('id', $userId);
            })
            ->pluck('role_code')
            ->toArray();

        if (empty($roleCodes)) {
            return [];
        }

        return $this->roleModel
            ->whereIn('role_code', $roleCodes)
            ->where('status', 1)
            ->pluck('permission_codes')
            ->toArray()
            ??
            [];
    }

    protected function findPermission(string $apiPath, string $httpMethod): ?object
    {
        $httpMethod = strtoupper($httpMethod);

        $permission = $this->permissionModel
            ->where('api_path', $apiPath)
            ->where('http_method', $httpMethod)
            ->first();

        if ($permission) {
            return $permission;
        }

        // 动态路径匹配
        $dynamicPermissions = $this->permissionModel
            ->where('api_path', 'like', '%{%')
            ->where('http_method', $httpMethod)
            ->get();

        foreach ($dynamicPermissions as $perm) {
            $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $perm->api_path);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $apiPath)) {
                return $perm;
            }
        }

        return null;
    }
}
```

- [ ] **Step 2: 提交**

```bash
git add app/Service/Permission/CheckUserPermission.php
git commit -m "refactor: simplify CheckUserPermission to use role.permission_codes"
```

---

## Task 12: Middleware 变更 - PermissionMiddleware

**Files:**
- Modify: `app/Middleware/PermissionMiddleware.php:77-86`

- [ ] **Step 1: 更新 PermissionMiddleware 鉴权查询**

```php
// 原查询（多表 join）：
$hasPermission = Db::table('sys_admin_role as ar')
    ->join('sys_role as r', 'ar.role_id', '=', 'r.id')
    ->join('sys_role_menu as rm', 'r.id', '=', 'rm.role_id')
    ->join('sys_menu_permission as mp', 'rm.menu_id', '=', 'mp.menu_id')
    ->where('ar.admin_id', $userId)
    ->where('mp.permission_id', $permissionId)
    ->where('r.status', 1)
    ->exists();

// 改为（直接查 role.permission_codes）：
$hasPermission = Db::table('sys_admin as a')
    ->join('sys_admin_role as ar', 'a.admin_code', '=', 'ar.admin_code')
    ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
    ->where('a.id', $userId)
    ->where('r.status', 1)
    ->whereJsonContains('r.permission_codes', $permCode)
    ->exists();
```

- [ ] **Step 2: 提交**

```bash
git add app/Middleware/PermissionMiddleware.php
git commit -m "refactor: update PermissionMiddleware to use role.permission_codes"
```

---

## Task 13: Controller 变更

**Files:**
- Modify: `app/Controller/System/MenuController.php`
- Modify: `app/Controller/System/RoleController.php`

- [ ] **Step 1: 更新 MenuController（删除 assignPermissions, removePermission）**

```php
// 删除以下方法：
// - assignPermissions()
// - removePermission()
```

- [ ] **Step 2: 更新 RoleController（assignMenus → assignPermissions）**

```php
// 原来：
public function assignMenus(): array
{
    $data = $this->request->all();
    $roleId = (int) ($data['role_id'] ?? 0);
    $menuIds = $data['menu_ids'] ?? [];
    $result = container()->get(RoleAction\AssignMenus::class)->handle($roleId, $menuIds);
    return $this->success($result, '分配成功');
}

// 改为：
public function assignPermissions(): array
{
    $data = $this->request->all();
    $roleCode = $data['role_code'] ?? '';
    $permissionCodes = $data['permission_codes'] ?? [];
    $result = container()->get(RoleAction\AssignPermissions::class)->handle($roleCode, $permissionCodes);
    return $this->success($result, '分配成功');
}

// 删除 removeMenu() 方法
```

- [ ] **Step 3: 提交**

```bash
git add app/Controller/System/MenuController.php app/Controller/System/RoleController.php
git commit -m "refactor: update controllers to match code-based RBAC"
```

---

## Task 14: Admin Permission Service 变更

**Files:**
- Modify: `app/Service/Admin/Permission/AssignRoles.php`
- Modify: `app/Service/Admin/Permission/GetPermissions.php`

- [ ] **Step 1: 更新 AssignRoles.php**

```php
public function handle(array $data): array
{
    $isSuperAdmin = currentUser('is_admin') == 1;
    if (!$isSuperAdmin) {
        paramError('只有超级管理员可以分配角色', 403);
    }

    $adminCode = $data['admin_code'] ?? '';
    $roleCodes = $data['role_codes'] ?? [];

    !empty($adminCode) || paramError('管理员标识不能为空');
    !empty($roleCodes) || paramError('角色标识不能为空');

    $admin = $this->adminModel->where('admin_code', $adminCode)->first();
    !$admin && paramError('管理员不存在');

    if ($admin->is_admin == 1) {
        paramError('不能修改超级管理员的角色', 403);
    }

    $roles = $this->roleModel->whereIn('role_code', $roleCodes)->get();
    $roles->count() === count($roleCodes) || paramError('部分角色不存在');

    // 删除旧关联，创建新关联
    $this->adminRoleModel->where('admin_code', $adminCode)->delete();
    foreach ($roleCodes as $roleCode) {
        $this->adminRoleModel->create([
            'admin_code' => $adminCode,
            'role_code' => $roleCode,
            'create_by' => (int) currentUser('id'),
        ]);
    }

    CacheKeys::flush(CacheKeys::PERM_USER . "{$admin->id}:", CacheKeys::menuUser($admin->id));

    return ['admin_code' => $adminCode, 'role_codes' => $roleCodes];
}
```

- [ ] **Step 2: 更新 GetPermissions.php**

```php
public function handle(int $staffId): array
{
    $admin = $this->adminModel->with(['roles'])->find($staffId);
    !$admin && paramError('管理员不存在');

    $permissions = [];
    $permissionCodes = [];

    foreach ($admin->roles as $role) {
        $permissions[] = [
            'role_code' => $role->role_code,
            'role_name' => $role->role_name,
            'permission_codes' => $role->permission_codes ?? [],
        ];
        $permissionCodes = array_merge($permissionCodes, $role->permission_codes ?? []);
    }

    // 去重
    $permissionCodes = array_unique($permissionCodes);

    return [
        'admin_id' => $staffId,
        'admin_code' => $admin->admin_code,
        'roles' => $permissions,
        'permission_codes' => array_values($permissionCodes),
    ];
}
```

- [ ] **Step 3: 提交**

```bash
git add app/Service/Admin/Permission/AssignRoles.php app/Service/Admin/Permission/GetPermissions.php
git commit -m "refactor: update admin permission services to use code-based RBAC"
```

---

## Task 15: Admin/Manage/GetMenu 变更

**Files:**
- Modify: `app/Service/Admin/Manage/GetMenu.php`

- [ ] **Step 1: 更新 GetMenu（菜单权限过滤逻辑）**

```php
public function handle(): array
{
    $isSuperAdmin = currentUser('is_admin') == 1;
    $userId = (int) currentUser('id');

    $cacheKey = $isSuperAdmin ? CacheKeys::MENU_SUPER : CacheKeys::menuUser($userId);
    $cached = $this->cache->get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    if ($isSuperAdmin) {
        $menus = $this->menuModel
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->toArray();
    } else {
        // 获取用户的 permission_codes
        $permissionCodes = $this->getUserPermissionCodes($userId);

        // 只返回有权限的菜单（permission_code 在用户的 permission_codes 中）
        $menus = $this->menuModel
            ->where('status', 1)
            ->when(!empty($permissionCodes), function ($q) use ($permissionCodes) {
                // 目录类型（type=0）总是显示，按钮/菜单必须有 permission_code
                $q->where(function ($sub) use ($permissionCodes) {
                    $sub->where('type', 0)  // 目录
                        ->orWhereIn('permission_code', $permissionCodes);
                });
            })
            ->orderBy('sort_order', 'asc')
            ->get()
            ->toArray();
    }

    $result = $this->menuModel->buildTree($menus);
    $this->cache->set($cacheKey, $result, CacheKeys::TTL_MEDIUM);

    return $result;
}

protected function getUserPermissionCodes(int $userId): array
{
    // 从 sys_admin_role + sys_role 获取 permission_codes
    return Db::table('sys_admin_role as ar')
        ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
        ->where('ar.admin_code', function ($q) use ($userId) {
            $q->select('admin_code')
                ->from('sys_admin')
                ->where('id', $userId);
        })
        ->where('r.status', 1)
        ->pluck('r.permission_codes')
        ->toArray()
        ??
        [];
}
```

- [ ] **Step 2: 提交**

```bash
git add app/Service/Admin/Manage/GetMenu.php
git commit -m "refactor: update GetMenu to filter by permission_codes"
```

---

## Task 16: 测试验证

**Files:**
- Test: `test/Cases/Unit/Service/Permission/CheckUserPermissionTest.php`

- [ ] **Step 1: 编写 CheckUserPermission 单元测试**

```php
<?php

declare(strict_types=1);

namespace Test\Cases\Unit\Service\Permission;

use PHPUnit\Framework\TestCase;

class CheckUserPermissionTest extends TestCase
{
    public function test_handle_with_public_permission(): void
    {
        // 公开权限直接返回 true
    }

    public function test_handle_with_valid_permission(): void
    {
        // 用户有 role.permission_codes 包含请求的 perm_code
    }

    public function test_handle_without_permission(): void
    {
        // 用户没有对应权限
    }
}
```

- [ ] **Step 2: 运行测试**

```bash
composer test
```

- [ ] **Step 3: 提交**

```bash
git add test/Cases/Unit/Service/Permission/CheckUserPermissionTest.php
git commit -m "test: add CheckUserPermission unit tests"
```

---

## 执行方式

**Plan complete and saved to `docs/superpowers/plans/2026-03-30-rbac-code-simplification.md`**

**Two execution options:**

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
