# 重构规范：把「取模型 + 校验」下沉到 BaseModel

## 背景

`app/Service` 下大量 Action 重复同一段样板：

```php
$admin = $this->adminModel->find($id);
! $admin && notFound('管理员');
```

已在 `app/Common/Model/BaseModel.php` 新增两个方法：

```php
// 按主键取模型；ID<=0 抛 400「{entity}ID不能为空」，不存在抛 404「{entity}不存在」
public function findOrNotFound(mixed $id, string $entity, array $with = []): static

// 按等值条件取模型；不存在抛 404「{entity}不存在」
public function firstOrNotFound(array $conditions, string $entity, array $with = []): static
```

**只下沉「单表取值 + ID 校验 + 不存在抛 404」这三件事。**
事务、缓存失效、跨表写入、状态判断、权限判断，一律留在 Action 里，不许动。

## 改动模式

### 模式 1：普通 find

```php
// 改前
$admin = $this->adminModel->find($id);
! $admin && notFound('管理员');

// 改后
$admin = $this->adminModel->findOrNotFound($id, '管理员');
```

### 模式 2：带关联预加载

```php
// 改前
$admin = $this->adminModel
    ->with(['roles', 'sessions'])
    ->find($id);
! $admin && notFound('管理员');

// 改后
$admin = $this->adminModel->findOrNotFound($id, '管理员', ['roles', 'sessions']);
```

注意：`with()` 的参数原样搬成第 3 个参数，不要改内容。

### 模式 3：已有 requireId，且 entity 文案一致

```php
// 改前
requireId($id, '字典');
$dict = $this->dictModel->find($id);
! $dict && notFound('字典');

// 改后
$dict = $this->dictModel->findOrNotFound($id, '字典');
```

（`findOrNotFound` 内部已经做过 `requireId`，不要重复）

### 模式 3b：requireId 与 notFound 文案不一致时（重要）

有两种写法并存：

- `requireId($id, '记录')` + `notFound('拉黑记录')` — **文案不同**
- `requireId($id, '模板')` + `notFound('邮件模板')` — **文案不同**

如果直接合成 `findOrNotFound($id, '拉黑记录')`，零 ID 时的报错会从「记录ID不能为空」变成「拉黑记录ID不能为空」，**会破坏已有测试断言**。

遇到这种文件，**保留 `requireId` 那一行不动**，只替换 `find + notFound` 两行：

```php
// 改前
requireId($id, '记录');
$record = $this->model->find($id);
! $record && notFound('拉黑记录');

// 改后（requireId 保留，文案不变）
requireId($id, '记录');
$record = $this->model->findOrNotFound($id, '拉黑记录');
```

重复校验一次 `requireId` 无害。判断方法：写完对比一下 `requireId` 的第二参数和 `notFound` 的参数，**不一致就保留 requireId**。

### 模式 4：按业务唯一键 first()

```php
// 改前
$role = $this->roleModel->where('role_code', $roleCode)->first();
! $role && notFound('角色');

// 改后
$role = $this->roleModel->firstOrNotFound(['role_code' => $roleCode], '角色');
```

只支持等值条件。如果原来的查询除了等值还有别的（`where('status', 1)`、`orderBy` 等），**不要改，保持原样**，并在报告里列出。

## 绝对禁止

1. **不要改** 事务里 `Db::beginTransaction()` / `commit` / `rollBack` 的任何结构。
2. **不要改** `CacheKeys::flush()` 的位置和参数。
3. **不要改** 任何 `paramError(...)` 的业务判断（如「已发布的通知不能删除」）。
4. **不要** 把 `notFound` 的 entity 文案改掉，一个字都不能动。
5. **不要** 动 `parallel([...])` 闭包里的 `find`。闭包内抛出的异常会被包装成 `ParallelExecutionException`，导致 404 变成 500。这类文件原样保留并在报告里说明。
6. **不要** 重构列表查询（`GetList`）、`Create`、业务状态机。
7. **不要** 阅读 `app/` 下其它无关文件，只读你被分配的文件和它对应的测试文件。
8. **不要** 修改 `BaseModel.php`、`helpers.php`、`config/` 下任何文件。

## 测试同步修改

现有测试 mock 的是 `find` / `with`，重构后要改成 mock `findOrNotFound`。

### 成功路径

```php
// 改前
$builder = Mockery::mock(Builder::class);
$builder->shouldReceive('find')->with(5)->andReturn($admin);
$model = Mockery::mock(SysAdminModel::class);
$model->shouldReceive('with')->with(['roles', 'sessions'])->andReturn($builder);

// 改后
$model = Mockery::mock(SysAdminModel::class);
$model->shouldReceive('findOrNotFound')
    ->with(5, '管理员', ['roles', 'sessions'])
    ->andReturn($admin);
```

无 `with` 的情况：

```php
$model->shouldReceive('findOrNotFound')->with(10, '管理员')->andReturn($admin);
```

### 记录不存在的路径

```php
// 改后
$model = Mockery::mock(SysAdminModel::class);
$model->shouldReceive('findOrNotFound')
    ->andThrow(new BusinessException(404, '管理员不存在'));

$this->expectException(BusinessException::class);
$this->expectExceptionCode(404);
$action->handle(999);
```

`BusinessException` 的构造签名是 `__construct(int $code, string $message)`，**code 在前**。

### 零 / 负 ID 的路径

如果 Action 原本就 `requireId`，测试里 `handle(0)` 会抛 400，保持原断言即可。
如果 Action 原本没有 `requireId`，重构后会新增 400 校验，测试需要补一条：

```php
public function testXxxRejectsZeroId(): void
{
    $model = Mockery::mock(XxxModel::class);

    $this->expectException(BusinessException::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessage('XXXID不能为空');

    (new Xxx($model))->handle(0);
}
```

**不要** 用 `makePartial()` 去 mock 这类模型——真实方法会走 `newQuery()` 打数据库。

## 验收

改完必须自己跑，不许跳过：

```bash
./scripts/verify-refactor.sh syntax
```

然后跑你负责的测试文件（把路径替换成你实际改的）：

```bash
set -a && . ./.env && set +a
/home/sky/.vfox/cache/php/v-8.5.12/php-8.5.12/bin/php vendor/bin/co-phpunit \
  --prepend test/bootstrap.php \
  --filter '你的测试文件名' 2>&1 | tail -20
```

判定标准：`Tests: N, Assertions: M` 且**没有 Failures / Errors**。
`Risky: N`（"did not remove its own error handlers"）是该项目既有的全局噪声，可忽略。

如果测试失败且你判断不是自己改出来的，**停下来报告**，不要为了通过而删测试或放宽断言。
