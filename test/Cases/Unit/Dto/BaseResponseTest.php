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

namespace HyperfTest\Cases\Unit\Dto;

use App\Dto\Menu\MenuResponse;
use App\Dto\Role\RoleResponse;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * 覆盖响应 DTO 的字段白名单裁剪与特殊字段转换.
 *
 * @internal
 * @coversNothing
 */
class BaseResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDetailKeepsOnlyWhitelistedFields(): void
    {
        $result = RoleResponse::detail([
            'id' => 1,
            'role_code' => 'ops',
            'role_name' => '运维',
            'password' => 'secret',
            'internal_flag' => 1,
        ]);

        $this->assertArrayHasKey('role_code', $result);
        // 白名单外字段（含敏感字段）不能外泄
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('internal_flag', $result);
    }

    public function testSavedReturnsOnlyIdentityFields(): void
    {
        $result = RoleResponse::saved(['id' => 3, 'role_code' => 'ops', 'role_name' => '运维', 'remark' => 'x']);

        $this->assertSame(['id' => 3, 'role_code' => 'ops', 'role_name' => '运维'], $result);
    }

    public function testCollectionTrimsEveryRow(): void
    {
        $result = RoleResponse::collection([
            ['id' => 1, 'role_code' => 'ops', 'password' => 'secret'],
            ['id' => 2, 'role_code' => 'dev', 'password' => 'secret'],
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayNotHasKey('password', $result[0]);
        $this->assertArrayNotHasKey('password', $result[1]);
    }

    public function testPageReturnsTotalAndList(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginatorInterface::class);
        $paginator->shouldReceive('items')->andReturn([
            ['id' => 1, 'role_code' => 'ops', 'password' => 'secret'],
        ]);
        $paginator->shouldReceive('total')->andReturn(11);

        $result = RoleResponse::page($paginator);

        $this->assertSame(11, $result['total']);
        $this->assertCount(1, $result['list']);
        $this->assertArrayNotHasKey('password', $result['list'][0]);
    }

    public function testMenuResponseSplitsPermissionCodeIntoArray(): void
    {
        // 库里存逗号分隔字符串，对外统一成数组
        $result = MenuResponse::detail(['id' => 1, 'menu_code' => 'sys', 'permission_code' => 'a,b']);

        $this->assertSame(['a', 'b'], $result['permission_codes']);
    }

    public function testMenuResponseReturnsEmptyArrayWhenNoPermissionCode(): void
    {
        $result = MenuResponse::detail(['id' => 1, 'menu_code' => 'sys', 'permission_code' => null]);

        $this->assertSame([], $result['permission_codes']);
    }

    public function testMenuResponseConvertsChildNodes(): void
    {
        $result = MenuResponse::collection([
            [
                'id' => 1,
                'menu_code' => 'sys',
                'permission_code' => 'a',
                'children' => [
                    ['id' => 2, 'menu_code' => 'sys-user', 'permission_code' => 'b,c'],
                ],
            ],
        ]);

        $this->assertSame(['a'], $result[0]['permission_codes']);
        $this->assertSame(['b', 'c'], $result[0]['children'][0]['permission_codes']);
    }
}
