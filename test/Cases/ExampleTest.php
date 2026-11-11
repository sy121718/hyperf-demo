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

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    public function testUnauthenticatedProfile(): void
    {
        // 根路径已交给前端，后端冒烟测试不应依赖是否已构建静态页面。
        $this->get('/api/auth/profile')->assertOk()->assertSee('TOKEN_MISSING');
    }
}
