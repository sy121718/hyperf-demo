# 任务分配表

每个子代理只处理自己那一组文件。改动模式与验收标准见 `scripts/refactor-spec.md`。

## 分组原则

- 按业务模块切分，避免两个代理同时改同一个测试文件。
- 每个分组内的文件互不重叠。
- **排除了两类文件**（由主代理或保留原样处理）：
  - `app/Service/Admin/Permission/RevokeRole.php`、`app/Service/Role/AssignRolesToAdmin.php`（`parallel` 闭包内有 `find`，改造会让 404 变 500）
  - `app/Service/Admin/Auth/Login.php`（`where()->first()` 后跟的是 `paramError('账号或密码错误')`，属于登录语义，不是 notFound）

## 组 A：Admin 模块

改以下文件（`find + notFound` → `findOrNotFound`）：

- `app/Service/Admin/Auth/ResetPassword.php` — entity `管理员`
- `app/Service/Admin/Manage/BanAdmin.php` — `管理员`
- `app/Service/Admin/Manage/Delete.php` — `管理员`
- `app/Service/Admin/Manage/ForceLogout.php` — `管理员`
- `app/Service/Admin/Manage/GetAdminFullInfo.php` — `管理员`，关联 `['roles', 'sessions']`
- `app/Service/Admin/Manage/GetInfo.php` — `管理员`，关联 `['roles', 'sessions']`
- `app/Service/Admin/Manage/KickUser.php` — `用户`
- `app/Service/Admin/Manage/ResetFailureCount.php` — `管理员`
- `app/Service/Admin/Manage/UnbanAdmin.php` — `管理员`
- `app/Service/Admin/Manage/Update.php` — `管理员`
- `app/Service/Admin/Permission/AssignRoles.php` — `管理员`，`where('admin_code')->first()` 用 `firstOrNotFound(['admin_code' => $adminCode], '管理员')`
- `app/Service/Admin/Permission/GetPermissions.php` — `管理员`，关联 `['roles']`
- `app/Service/Admin/Permission/GetRoles.php` — `管理员`
- `app/Service/Admin/Profile/ChangePassword.php` — `用户`
- `app/Service/Admin/Profile/GetProfile.php` — `用户`，关联 `['roles:sys_role.id,sys_role.role_name,sys_role.role_code']`
- `app/Service/Admin/Profile/UpdateProfile.php` — `用户`

对应测试：

- `test/Cases/Unit/Service/Admin/Auth/ResetPasswordTest.php`
- `test/Cases/Unit/Service/Admin/Manage/AdminExtraTest.php`
- `test/Cases/Unit/Service/Admin/Manage/DeleteTest.php`
- `test/Cases/Unit/Service/Admin/Manage/GetInfoTest.php`
- `test/Cases/Unit/Service/Admin/Manage/UpdateTest.php`
- `test/Cases/Unit/Service/Admin/Profile/ProfileTest.php`

## 组 B：内容与文件模块

- `app/Service/Attachment/Delete.php` — `附件`
- `app/Service/Attachment/Update.php` — `附件`（第 34 行对 `SysFileCategoryModel` 的 `exists` 判断保持原样，不动）
- `app/Service/Config/Delete.php` — `配置`
- `app/Service/Config/GetInfo.php` — `配置`
- `app/Service/Config/Update.php` — `配置`
- `app/Service/CronJob/Delete.php` — `任务`
- `app/Service/CronJob/GetInfo.php` — `任务`
- `app/Service/CronJob/Run.php` — `定时任务`（注意文案是「定时任务」，不是「任务」）
- `app/Service/CronJob/Update.php` — `任务`
- `app/Service/Dict/Delete.php` — `字典`
- `app/Service/Dict/GetInfo.php` — `字典`，关联 `['items']`
- `app/Service/Dict/Update.php` — `字典`
- `app/Service/Email/Template/Delete.php` — `邮件模板`（该文件无 `requireId`，直接合并）
- `app/Service/Email/Template/Update.php` — `邮件模板`，**但 `requireId` 用的是「模板」，文案不一致 → 按规范模式 3b 保留 requireId 那行**
- `app/Service/IpBlacklist/Unban.php` — `拉黑记录`，**`requireId` 是「记录」，文案不一致 → 按规范模式 3b 保留 requireId 那行**
- 自查 `app/Service/Dict/Update.php`、`app/Service/Dict/GetInfo.php`、`app/Service/Dict/Delete.php`：`requireId` 与 `notFound` 都是「字典」，可安全合并。

对应测试：

- `test/Cases/Unit/Service/Attachment/AttachmentTest.php`
- `test/Cases/Unit/Service/Config/ConfigTest.php`
- `test/Cases/Unit/Service/CronJob/CronJobTest.php`
- `test/Cases/Unit/Service/Dict/DictTest.php`
- `test/Cases/Unit/Service/Email/EmailTest.php`
- `test/Cases/Unit/Service/IpBlacklist/IpBlacklistTest.php`

## 组 C：角色、通知、会话、菜单、权限字典

- `app/Service/Role/AssignMenus.php` — `角色`，`where('role_code')->first()` → `firstOrNotFound(['role_code' => $roleCode], '角色')`
- `app/Service/Role/AssignPermissions.php` — `角色`，同上
- `app/Service/Role/AssignRoles.php` — `管理员`
- `app/Service/Role/Delete.php` — `角色`
- `app/Service/Role/GetInfo.php` — `角色`
- `app/Service/Role/RemoveRoleFromAdmin.php` — 两处：`管理员` + `角色`
- `app/Service/Role/Update.php` — `角色`
- `app/Service/Notice/Delete.php` — `通知`（在事务内，结构不动）
- `app/Service/Notice/Publish.php` — `通知`（在事务内，结构不动）
- `app/Service/Notice/SetTargets.php` — `通知`
- `app/Service/Notice/Update.php` — `通知`（在事务内，结构不动）
- `app/Service/Notice/Withdraw.php` — `通知`（在事务内，结构不动）
- `app/Service/Ops/Session/GetInfo.php` — `会话`，关联 `['admin:id,username,name']`
- `app/Service/Ops/Session/Kickout.php` — `会话`
- `app/Service/Menu/Delete.php` — `菜单`
- `app/Service/Menu/GetInfo.php` — `菜单`
- `app/Service/Menu/Update.php` — `菜单`
- `app/Service/Permission/Delete.php` — `权限`
- `app/Service/Permission/GetInfo.php` — `权限`
- `app/Service/Permission/Update.php` — `权限`

对应测试：

- `test/Cases/Unit/Service/Notice/NoticeTest.php`
- `test/Cases/Unit/Service/Ops/Session/SessionExtraTest.php`
- `test/Cases/Unit/Service/Permission/PermissionTest.php`

（Role / Menu 目前没有独立测试文件，只需保证语法通过，不要新建测试文件。）
