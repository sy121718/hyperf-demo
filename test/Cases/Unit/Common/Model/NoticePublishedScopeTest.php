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

namespace HyperfTest\Cases\Unit\Common\Model;

use App\Model\Notify\NoticeModel;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;

/**
 * 锁定「有效公告」的口径.
 *
 * 背景：列表曾用 `status=1 AND (expire_time IS NULL OR expire_time > now())`，
 * 未读数曾用 `status=1 AND expire_time > now()`。两者不一致导致
 * 长期有效公告（expire_time 为 NULL）出现在列表里但永远不算未读。
 * 现统一为 NoticeModel::scopePublished()，本测试锁住 SQL 形态防回退。
 *
 * @internal
 * @coversNothing
 */
class NoticePublishedScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testPublishedIncludesNullExpireTime(): void
    {
        $sql = NoticeModel::query()->published()->toSql();

        // 必须带 NULL 分支，否则长期有效公告会被漏掉
        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('`expire_time` is null', $sql);
        $this->assertStringContainsString('`expire_time` > ?', $sql);
    }

    public function testPublishedBindsStatusAndTimeInOrder(): void
    {
        $builder = NoticeModel::query()->published();

        // bindings: 第 1 个是 status=1，第 2 个是过期时间比较值
        $this->assertSame(1, $builder->getBindings()[0]);
        $this->assertIsString($builder->getBindings()[1]);
    }

    public function testPublishedGroupsOrCondition(): void
    {
        $sql = NoticeModel::query()->published()->toSql();

        // NULL 与过期时间必须是同一个括号内的 OR，不能和 status 平铺成
        // status=1 AND expire_time IS NULL OR expire_time > ?（优先级错误）
        $this->assertMatchesRegularExpression(
            '/`status` = \? and \(`expire_time` is null or `expire_time` > \?\)/',
            $sql
        );
    }
}
