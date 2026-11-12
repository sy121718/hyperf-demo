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

use App\Dto\BaseResponse;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * 响应字段白名单的取值规则.
 * @internal
 */
#[CoversNothing]
class BaseResponsePickTest extends TestCase
{
    public function testPicksPlainFields(): void
    {
        $result = $this->pick(['id' => 1, 'name' => 'a', 'secret' => 'x'], ['id', 'name']);

        $this->assertSame(['id' => 1, 'name' => 'a'], $result);
    }

    /**
     * 普通字段为 null 时仍要输出，保持响应结构稳定.
     */
    public function testKeepsNullPlainFields(): void
    {
        $result = $this->pick(['id' => 1, 'remark' => null], ['id', 'remark']);

        $this->assertArrayHasKey('remark', $result);
        $this->assertNull($result['remark']);
    }

    /**
     * 'admin_name' => 'admin.name' 从预加载的关联对象里取值并改名.
     */
    public function testPicksNestedRelationField(): void
    {
        $row = [
            'id' => 1,
            'admin_id' => 7,
            'admin' => ['id' => 7, 'name' => '超级管理员', 'username' => 'admin'],
        ];

        $result = $this->pick($row, ['id', 'admin_id', 'admin_name' => 'admin.name']);

        $this->assertSame('超级管理员', $result['admin_name']);
        $this->assertArrayNotHasKey('admin', $result, '关联数组本身不应出现在输出里');
    }

    /**
     * 关联未预加载（字段缺失）时不输出该键，避免前端拿 null 覆盖默认文案.
     */
    public function testOmitsMissingRelationField(): void
    {
        $result = $this->pick(['id' => 1, 'admin_id' => 7], ['id', 'admin_name' => 'admin.name']);

        $this->assertArrayNotHasKey('admin_name', $result);
    }

    /**
     * 关联为 null（如外键指向已删除管理员）时同样不输出.
     */
    public function testOmitsNullRelationField(): void
    {
        $row = ['id' => 1, 'admin' => null];

        $result = $this->pick($row, ['id', 'admin_name' => 'admin.name']);

        $this->assertArrayNotHasKey('admin_name', $result);
    }

    public function testSupportsDeeperPath(): void
    {
        $row = ['a' => ['b' => ['c' => 'deep']]];

        $result = $this->pick($row, ['value' => 'a.b.c']);

        $this->assertSame('deep', $result['value']);
    }

    public function testEmptyWhitelistReturnsRowAsIs(): void
    {
        $row = ['id' => 1, 'anything' => 'x'];

        $this->assertSame($row, $this->pick($row, []));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int|string, string> $fields
     * @return array<string, mixed>
     */
    private function pick(array $row, array $fields): array
    {
        return (new class extends BaseResponse {
            public static function pickForTest(array $row, array $fields): array
            {
                return self::pick($row, $fields);
            }
        })::pickForTest($row, $fields);
    }
}
