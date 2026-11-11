# RBAC 权限管理系统 - AI 助手规则

## 项目概述

**技术栈**: Hyperf 3.2 + PHP 8.5 + Swoole 6.2 + MySQL + JWT + Vue
**数据库**: `hyper`

## 核心规范

### 代码规范
- **PHP**: 8.5+, **Hyperf**: 3.2, **Swoole**: 6.2
- **编码**: PSR-12
- **类型**: 必须开启 `declare(strict_types=1);`，完整类型注解
- **测试**: 每个 Action 必须有完善的单元测试
- **批量操作**: 禁止批量操作（容易卡住），单个文件处理后验证再继续

### 架构：Service 层原子化拆解

**核心原则**：每个业务操作拆解为独立的 Action 类

```
app/Service/{模块}/
├── {子模块}/          # 按业务领域划分
│   ├── {Action}.php   # 独立 Action 类（如 Create.php, Update.php）
│   └── Dto/           # 数据传输对象（前端字段 ↔ 数据库字段映射）
├── Logic/             # 复杂业务逻辑（>50 行时拆分）
└── Utils/             # 无状态工具方法
```

**各层职责**：

| 层级 | 职责 | 关键规则 |
|------|------|----------|
| **Controller** | 接收请求 → 调用 Action → 返回响应 | 使用 FormRequest 验证，用 `container()->get()` 调用 Action |
| **Request** | 请求验证 + 字段转换 | 用前端字段名定义 rules()，关联 DTO，validated() 自动转换为数据库字段名 |
| **Action** | 单一业务操作 | `handle()` 方法，用 `paramError()` 抛异常，写操作用 `Db::transaction()` |
| **DTO** | 定义前端↔数据库字段映射 | 继承 `BaseDto`，提供 `dbToFront()` / `toDbArray()` 双向转换 |
| **Model** | 数据定义 | 继承 `BaseModel`（在 Common/Model/ 下），利用基类 filter/sort/whereAny |

### DTO 双向转换数据流

```
前端请求 (camelCase 字段名)
    │
    ▼
Request 验证器 (用前端字段名定义 rules)
    │  validated() 自动通过 DTO $fields 映射
    ▼
Controller 拿到数据库字段名的 $data
    │  调用 Action
    ▼
Action 处理业务 (全程使用数据库字段名)
    │  返回结果
    ▼
DTO::dbToFront() 把数据库字段名转回前端字段名
    │
    ▼
Controller -> success() 返回 JSON 给前端
```

**Controller 调用 Action 标准写法**：

```php
use App\Service\Admin\Auth as AuthAction;

public function login(LoginRequest $request): array
{
    $data = $request->validated();
    $result = container()->get(AuthAction\Login::class)->handle($data);
    return $this->success($result, '登录成功');
}
```

**命名规范**：
- 别名：子模块名 + `Action` 后缀（如 `AuthAction`）
- 引用：`use App\Service\{模块}\{子模块} as {子模块}Action;`
- 调用：`container()->get({别名}\{Action类名}::class)->handle($data)`

**禁止事项**：
- 禁止创建 `{模块}Service` 聚合类
- 禁止直接 `new` 实例化 Action
- Controller 中禁止写业务逻辑

## 响应与异常规范

只有两个核心函数：

**1. `paramError($msg, $code=400)` -- 全局函数，任何地方抛业务异常**

```php
paramError('用户名不能为空');           // 400
paramError('用户未登录', 401);          // 401
paramError('权限不足', 403);            // 403
paramError('数据不存在', 404);          // 404
```

**2. `$this->success($data, $msg)` -- 仅 Controller 中使用**

```php
return $this->success($result, '创建成功');
// 返回：{ success: true, code: 200, message: "创建成功", data: ... }
```

**原则**：成功一律 200，失败当场 `paramError()` 抛出，由全局异常处理器统一捕获返回 JSON。

## 数据库查询规范

**优先 Hyperf ORM 原生方法**：
- 单表：`where()`, `when()`, `whereHas()`
- 多字段模糊：`scopeWhereAny()`（BaseModel 中实现，替代 Laravel 的 whereAny）
- 多表：`leftJoin()`, `join()` + `select()`
- 公共工具类（WhereConditionParser 等）：仅用于多主表复杂联合查询

**Model 查询白名单配置**：

```php
class SysAdminModel extends BaseModel
{
    // 精确过滤字段白名单（用于 filter 作用域）
    protected array $whereableFields = [
        'username', 'status', 'is_admin', 'email', 'phone',
    ];

    // 模糊搜索字段白名单（用于 whereAny）
    protected array $searchableFields = [
        'username', 'name', 'email', 'phone',
    ];

    // 排序字段白名单（用于 sort 作用域）
    protected array $orderableFields = [
        'id', 'username', 'name', 'status', 'create_time', 'update_time',
    ];
}
```

**Action 查询标准写法**：

```php
public function handle(array $data = [])
{
    $search = $data['search'] ?? null;

    return $this->roleModel
        ->with(['creator', 'updater'])
        ->filter($data)
        ->when($search, fn($q) => $q->whereAny(
            $this->roleModel->getSearchableFields(), 'like', "%{$search}%"
        ))
        ->sort($data)
        ->paginate($data['limit'] ?? 10, ['*'], 'page', $data['page'] ?? 1);
}
```

**关键要点**：
1. **白名单防护**：`whereableFields`、`searchableFields`、`orderableFields` 防止 SQL 注入
2. **提前处理空值**：`$search = $data['search'] ?? null;` 简化 `when()` 判断
3. **链式调用**：保持查询构建器的流畅性
4. **whereAny**：通过 BaseModel 的 `scopeWhereAny` 实现（Hyperf ORM 不支持原生 whereAny）

**前后端字段名转换**：

```php
// DTO 配置：前端字段 → 数据库字段
protected static array $fields = [
    'menuName' => 'menu_name',  // 前端 camelCase → 数据库 snake_case
    'mobile' => 'phone',        // 字段名不同时映射
];

// 验证器：使用前端字段名验证
public function rules(): array
{
    return ['menuName' => 'required|string', 'mobile' => 'nullable'];
}

// Controller：validated() 自动转换
$data = $request->validated();  // 自动转换为数据库字段名

// Controller：返回时用 DTO 转回前端字段名
return $this->success(MenuDto::dbToFront($result->toArray()), '获取成功');
```

**辅助函数**：
- `paramError($msg, $code=400)`: 抛业务异常（全局）
- `currentUser($field)`: 获取当前 JWT 用户（通过 Context）
- `container()`: 获取 DI 容器
- `millisecond()`: 获取当前 UTC 时间

## 与 Laravel 的关键差异

| Laravel | Hyperf |
|---------|--------|
| `app(Class::class)` | `container()->get(Class::class)` |
| `auth('admin')->user()` | `currentUser()` (通过 Context) |
| `response()->json()` | 直接返回数组，Hyperf 自动 JSON 序列化 |
| `DB::transaction()` | `Db::transaction()` |
| `Hash::check()` | `password_verify()` |
| `request()->ip()` | `$request->getServerParams()['remote_addr']` |
| `whereAny()` | `->whereAny()` (BaseModel scopeWhereAny) |
| FormRequest | `app/Request/BaseRequest` (继承 Hyperf FormRequest) |

## 目录结构

```
app/
├── Controller/                    # 控制器层
│   ├── AbstractController.php     # 基础控制器（success 方法）
│   └── System/                    # 系统管理模块
├── Request/                       # 请求验证层（独立目录）
│   ├── BaseRequest.php            # 基础请求（关联 DTO 自动转换）
│   └── System/                    # 按模块分目录
├── Service/                       # 业务层（Action 原子化）
│   ├── Admin/Auth/                # 认证
│   ├── Admin/Manage/              # 管理员 CRUD
│   │   └── Dto/                   # DTO 与 Action 同级
│   ├── Role/                      # 角色
│   ├── Menu/                      # 菜单
│   ├── Permission/                # 权限字典
│   └── Log/                       # 日志
├── Model/                         # 数据模型层（业务模型）
│   ├── Admin/                     # SysAdminModel, SysAdminRoleModel, SysAdminSessionsModel
│   ├── Role/                      # SysRoleModel, SysRoleMenuModel
│   ├── Menu/                      # SysMenuModel, SysMenuPermissionModel
│   ├── Permission/                # SysPermissionDictModel
│   └── Log/                       # SysLoginLogModel, SysOperationLogModel
├── Middleware/                    # 中间件
│   ├── JwtAuthMiddleware.php      # JWT 认证 + Session 验证
│   ├── PermissionMiddleware.php   # 按钮级权限验证
│   └── CorsMiddleware.php         # 跨域
├── Exception/                     # 异常处理
│   ├── BusinessException.php      # 业务异常类
│   └── Handler/AppExceptionHandler.php
├── Common/                        # 公共基础设施
│   ├── Model/BaseModel.php        # 模型基类
│   ├── Dto/BaseDto.php            # DTO 基类
│   ├── Utils/                     # 工具类
│   └── helpers.php                # 全局辅助函数
```

## MCP 工具与环境说明

**环境**: WSL Ubuntu - 部分操作可直接用 bash 命令

| 操作 | 推荐方式 |
|------|----------|
| 数据库查询/操作 | 优先 bash mysql 命令（直接快速） |
| 文件操作 | Read/Edit/Write 工具 |
| 代码搜索 | Grep/Glob 工具 |

**数据库配置** (`.env`)：
```bash
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hyper
DB_USERNAME=root
DB_PASSWORD=root
```

**数据库操作示例**：
```bash
mysql -h 127.0.0.1 -P 3306 -u root -proot hyper

# 查看表结构
DESCRIBE sys_admin;

# 执行查询
SELECT * FROM sys_admin WHERE id = 1;
```

> **注意**: 配置修改直接编辑 `.env` 文件

## 执行流程

```
1. 探索阶段
   └─ 搜索现有代码（Service/Utils/Models），避免重复造轮子

2. 开发阶段
   ├─ DTO（定义字段映射：前端 → 数据库）
   ├─ Model（配置字段、关联、查询白名单）
   ├─ Request（使用前端字段名定义验证规则，关联 DTO）
   ├─ Actions（GetList, Create, Update, Delete）
   └─ Controller（使用 $request->validated() 自动获取转换后数据）

3. 测试阶段
   ├─ 编写单元测试
   └─ 运行 composer test 确保通过

4. 下一模块
   └─ 重复上述流程
```

## 开发检查清单

- [ ] 结构：符合 Service/Action/Logic/Utils 结构
- [ ] 类型：开启 `strict_types=1`，完整类型注解
- [ ] 异常：使用 `paramError()` 而非 `throw new Exception`
- [ ] 事务：写操作用 `Db::transaction()` 包裹
- [ ] 调用：Controller 用 `container()->get()` + 命名空间别名
- [ ] 转换：Request 关联 DTO，返回时用 `Dto::dbToFront()`
- [ ] 测试：模块完成后测试必须通过
