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

namespace HyperfTest\Cases\Unit\Service\Ops\Session;

use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Ops\Session\CreateSession;
use DateTimeImmutable;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use stdClass;

use function Hyperf\Support\env;

/**
 * @internal
 * @coversNothing
 */
class CreateSessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    // ==================== 1. 基本创建 ====================

    public function testCreatesSessionWithRequiredFields(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 5,
            'token' => 'jwt.token.here',
            'user_agent' => 'Chrome/120',
            'ip' => '10.0.0.1',
        ]);

        $d = $holder->data;
        $this->assertSame(5, $d['admin_id']);
        $this->assertSame('jwt.token.here', $d['jwt_token']);
        $this->assertSame('10.0.0.1', $d['login_ip']);
        $this->assertSame('Chrome/120', $d['device_info']);
        $this->assertSame(1, $d['status']);
        $this->assertSame(5, $d['create_by']);
    }

    public function testSessionHasAllExpectedKeys(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => 'ua', 'ip' => '1.1.1.1',
        ]);

        $required = [
            'admin_id', 'device_type', 'device_info', 'jwt_token',
            'login_ip', 'login_time', 'last_active_time', 'expires_time',
            'status', 'create_by',
        ];
        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $holder->data, "缺少必需字段: {$key}");
        }
    }

    // ==================== 2. 时间戳格式 ====================

    public function testLoginTimeIsMillisecondFormat(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/',
            $holder->data['login_time'],
            'login_time 应该是 Y-m-d H:i:s.v 毫秒格式'
        );
    }

    public function testLoginTimeEqualsLastActiveTime(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $this->assertSame($holder->data['login_time'], $holder->data['last_active_time']);
    }

    public function testExpiresTimeIsAfterLoginTime(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $login = new DateTimeImmutable($holder->data['login_time']);
        $expires = new DateTimeImmutable($holder->data['expires_time']);
        $this->assertGreaterThan($login, $expires);
    }

    public function testExpiresTimeRoughlyMatchesTtl(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $ttl = (int) env('JWT_TTL', 7200);
        $login = new DateTimeImmutable($holder->data['login_time']);
        $expires = new DateTimeImmutable($holder->data['expires_time']);
        $diff = $expires->getTimestamp() - $login->getTimestamp();
        $this->assertEqualsWithDelta($ttl, $diff, 2, 'expires - login 应约等于 TTL');
    }

    // ==================== 3. 设备类型检测 ====================

    public function testDeviceTypeDesktopForChrome(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0',
            'ip' => '',
        ]);

        $this->assertSame('desktop', $holder->data['device_type']);
    }

    public function testDeviceTypeMobileForAndroid(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13; Pixel 7) Mobile Safari/537.36',
            'ip' => '',
        ]);

        $this->assertSame('mobile', $holder->data['device_type']);
    }

    public function testDeviceTypeMobileForIphone(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            'ip' => '',
        ]);

        $this->assertSame('mobile', $holder->data['device_type']);
    }

    public function testDeviceTypeTabletForIpad(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't',
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            'ip' => '',
        ]);

        $this->assertSame('tablet', $holder->data['device_type']);
    }

    public function testDeviceTypeDesktopForEmptyUserAgent(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $this->assertSame('desktop', $holder->data['device_type']);
    }

    // ==================== 4. User-Agent 截断 ====================

    public function testUserAgentTruncatedAt200Chars(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        $longUa = str_repeat('A', 300);
        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => $longUa, 'ip' => '',
        ]);

        $this->assertSame(200, mb_strlen($holder->data['device_info']));
    }

    public function testShortUserAgentNotTruncated(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't', 'user_agent' => 'Firefox/130', 'ip' => '',
        ]);

        $this->assertSame('Firefox/130', $holder->data['device_info']);
    }

    // ==================== 5. 默认值 & 缺失字段 ====================

    public function testMissingUserAgentDefaultsToEmpty(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle(['admin_id' => 1, 'token' => 't']);

        $this->assertSame('', $holder->data['device_info']);
        $this->assertSame('desktop', $holder->data['device_type']);
    }

    public function testMissingIpDefaultsToEmpty(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle(['admin_id' => 1, 'token' => 't']);

        $this->assertSame('', $holder->data['login_ip']);
    }

    public function testMissingTokenDefaultsToEmpty(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle(['admin_id' => 1]);

        $this->assertSame('', $holder->data['jwt_token']);
    }

    public function testMissingAdminIdDefaultsToZero(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([]);

        $this->assertSame(0, $holder->data['admin_id']);
        $this->assertSame(0, $holder->data['create_by']);
    }

    // ==================== 6. create_by 正确性 ====================

    public function testCreateByMatchesAdminId(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 77, 'token' => 't', 'user_agent' => '', 'ip' => '',
        ]);

        $this->assertSame(77, $holder->data['create_by']);
    }

    // ==================== 7. Mobile 优先于 Tablet ====================

    public function testMobileKeywordTakesPriorityOverTablet(): void
    {
        [$model, $holder] = $this->buildAndCapture();

        (new CreateSession($model))->handle([
            'admin_id' => 1, 'token' => 't',
            'user_agent' => 'Mozilla/5.0 (iPad; Mobile) Safari',
            'ip' => '',
        ]);

        $this->assertSame(
            'mobile',
            $holder->data['device_type'],
            'mobile 关键字应优先于 tablet'
        );
    }

    /**
     * @return array{0: MockInterface, 1: stdClass}
     */
    private function buildAndCapture(): array
    {
        $holder = new stdClass();
        $holder->data = null;

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) use ($holder) {
                $holder->data = $data;
                return true;
            })
            ->andReturn(Mockery::mock(stdClass::class));

        return [$model, $holder];
    }
}
