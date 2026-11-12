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
├── {子模块}/          # 按业务领域划分（模块下无子模块时直接放 Action）
│   └── {Action}.php   # 独立 Action 类（如 Create.php, Update.php）
└── Traits/            # 跨模块共用的 Action 行为
```

**各层职责**：

| 层级 | 职责 | 关键规则 |
|------|------|----------|
| **Controller** | 接收请求 → 调用 Action → 返回响应 | 用 `container()->get()` 调用 Action，不碰 Model、不写业务判断 |
| **Request DTO** | 请求验证 + 参数承载 | `app/Dto/{模块}/{模块}Request` 继承 `BaseRequestDto`，按 `getRouteAction()` 分支返回 rules |
| **Action** | 单一业务操作 | `handle()` 方法接收 Request DTO，用 `paramError()` 抛异常，写操作用 `Db::transaction()` |
| **Response DTO** | 输出字段裁剪 | `app/Dto/{模块}/{模块}Response` 继承 `BaseResponse`，用 `page/collection/detail/saved` 白名单输出 |
| **Model** | 数据定义 | 继承 `App\Common\Model\BaseModel`，利用基类 filter/sort/whereAny，关联定义与查询入口都放这里 |

### 请求到响应的数据流

接口层字段统一用 snake_case（与数据库列同名），不做 camelCase 转换：

```
前端请求 (snake_case，如 menu_name / create_time)
    │
    ▼
请求 DTO (BaseRequestDto) 按路由动作选 rules 校验
    │  validateResolved() 把校验后的值按属性声明类型写回
    ▼
Controller 直接把 DTO 传给 Action
    │
    ▼
Action 用 $request->toDbArrayFiltered() 等取写库数据（全程 snake_case）
    │  返回模型/分页器
    ▼
响应 DTO (BaseResponse) 按 listFields/detailFields/savedFields 白名单裁剪
    │
    ▼
Controller -> success() 返回 JSON 给前端
```

**Controller 调用 Action 标准写法**：

```php
use App\Service\Admin\Auth as AuthAction;

public function login(LoginRequest $request): array
{
    $result = container()->get(AuthAction\Login::class)->handle($request);
    return $this->success($result, '登录成功');
}
```

**命名规范**：
- 别名：子模块名 + `Action` 后缀（如 `AuthAction`）
- 引用：`use App\Service\{模块}\{子模块} as {子模块}Action;`
- 调用：`container()->get({别名}\{Action类名}::class)->handle($request)`

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
public function handle(RoleRequest $request): mixed
{
    // 列表页只查必要字段，详情模式才取全字段
    $fields = $request->withDetail() ? ['*'] : [
        'id', 'role_code', 'role_name', 'status', 'sort_order', 'remark', 'create_time',
    ];

    return $this->roleModel
        ->filter($request->toQueryArray())
        ->when($request->search, fn ($q) => $q->whereAny(
            $this->roleModel->getSearchableFields(),
            'like',
            "%{$request->search}%"
        ))
        ->sort($request->toQueryArray())
        ->paginate($request->pageSize(), $fields, 'page', $request->pageNumber());
}
```

**关键要点**：
1. **白名单防护**：`whereableFields`、`searchableFields`、`orderableFields` 防止 SQL 注入
2. **参数来源**：列表参数用 `toQueryArray()`（业务字段 + page/limit/search/sort），写库数据用 `toDbArrayFiltered()`
3. **链式调用**：保持查询构建器的流畅性
4. **whereAny**：通过 BaseModel 的 `scopeWhereAny` 实现（Hyperf ORM 不支持原生 whereAny）
5. **多表数据**：优先在 Model 里定义关联并用 `with()` 预加载，避免在 Action 里手写 join

**请求 DTO 与响应 DTO 分工**：

```php
// 请求侧：rules 的键就是 DTO 属性名，也就是数据库列名
public function rules(): array
{
    return match ($this->getRouteAction()) {
        'create' => ['menu_name' => 'required|string|max:50', ...],
        'update' => ['id' => 'required|integer|min:1', 'menu_name' => 'nullable|string|max:50', ...],
        default => [],
    };
}

// 响应侧：白名单决定输出字段，不在白名单里的列不会出现在 JSON 中
class MenuResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return ['id', 'menu_name', 'parent_id', 'type', 'status', 'create_time'];
    }
}
```

响应 DTO 的四个入口：`page(分页器)` → `{total, list}`、`collection(数组)`、`detail(单条)`、`saved(写操作回执，默认只回 id)`。

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
| FormRequest | `app/Dto/{模块}/{模块}Request` (继承 `BaseRequestDto`) |

## 目录结构

```
app/
├── Controller/                    # 控制器层（只调 Action、组织响应）
│   ├── AbstractController.php     # 基础控制器（success 方法）
│   ├── System/                    # 系统管理：Admin/Role/Menu/Permission/Dict/Config/Cache/CronJob/Attachment/Auth
│   ├── Notify/                    # 通知：Notice/Email
│   └── Ops/                       # 运维：Log/Session
├── Dto/                           # 请求 DTO + 响应 DTO（每模块一对）
│   ├── BaseRequestDto.php         # 请求基类（校验 + hydrate + toDbArray）
│   ├── BaseResponse.php           # 响应基类（page/collection/detail/saved）
│   └── {模块}/{模块}Request.php / {模块}Response.php
├── Service/                       # 业务层（Action 原子化）
│   ├── Admin/Auth|Manage|Permission|Profile/
│   ├── Role/ Menu/ Permission/ Dict/ Config/ Cache/ CronJob/
│   ├── Attachment/ Notice/ Email/Send|Template/
│   ├── Ops/LoginLog|OperationLog|Session/
│   ├── Log/                       # 操作日志记录
│   ├── IpBlacklist/
│   └── Traits/                    # 跨模块 Action 行为（如 CheckOwnership）
├── Model/                         # 数据模型层
│   ├── Admin/                     # SysAdminModel, SysAdminRoleModel, SysAdminSessionsModel,
│   │                              # SysLoginLogModel, SysOperationLogModel
│   ├── Rbac/                      # SysRoleModel, SysMenuModel, SysPermissionDictModel
│   ├── System/                    # SysConfigModel, SysDictModel, SysDictItemModel, IpBlacklistModel
│   ├── Notify/                    # NoticeModel, NoticeTargetModel, NoticeReadModel,
│   │                              # EmailTemplateModel, EmailSendRecordModel, EmailSendRecipientModel
│   ├── Attachment/                # SysAttachmentModel, SysFileCategoryModel
│   └── CronJob/                   # SysCronJobModel
├── Middleware/                    # 中间件
│   ├── JwtAuthMiddleware.php      # JWT 认证 + Session 验证
│   ├── PermissionMiddleware.php   # 按钮级权限验证
│   ├── CorsMiddleware.php         # 跨域
│   └── FrontendMiddleware.php     # 前端静态资源
├── Exception/                     # 异常处理
│   ├── BusinessException.php      # 业务异常类
│   └── Handler/AppExceptionHandler.php
├── Common/                        # 公共基础设施
│   ├── Model/BaseModel.php        # 模型基类（filter/sort/whereAny/findOrNotFound/buildTree）
│   ├── Utils/                     # 工具类（TreeBuilder、条件解析器、字段唯一性检查）
│   ├── CacheKeys.php              # 缓存 key 常量 + flush
│   └── helpers.php                # 全局辅助函数
```

**权限存储方式**：角色权限存在 `sys_role.permission_codes`（已配 array cast 的 JSON 列），
没有 `sys_role_menu` / `sys_menu_permission` 关联表。权限码的统一解析入口是
`SysAdminModel::permissionCodes()`，菜单过滤与权限中间件都走它。

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
   ├─ Model（配置字段、关联、查询白名单）
   ├─ 请求 DTO（按路由动作定义 rules，声明 snake_case 属性）
   ├─ 响应 DTO（定义 listFields / detailFields / savedFields）
   ├─ Actions（GetList, Create, Update, Delete）
   └─ Controller（把 DTO 直传 Action，用响应 DTO 组装输出）

3. 测试阶段
   ├─ 编写单元测试
   └─ 运行 composer test 确保通过

4. 下一模块
   └─ 重复上述流程
```

## 开发检查清单

- [ ] 结构：每个业务操作一个 Action 类，放在 `app/Service/{模块}/`
- [ ] 类型：开启 `strict_types=1`，完整类型注解
- [ ] 异常：使用 `paramError()` 而非 `throw new Exception`
- [ ] 事务：写操作用 `Db::transaction()` 包裹
- [ ] 调用：Controller 用 `container()->get()` + 命名空间别名
- [ ] 输出：Controller 用响应 DTO 的 `page/collection/detail/saved` 裁剪字段
- [ ] 边界：Controller 不引用 Model、不写业务判断，只协调 Action
- [ ] 测试：模块完成后测试必须通过
