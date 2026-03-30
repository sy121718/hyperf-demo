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

namespace App\Service\Ops\Session;

use App\Common\CacheKeys;
use App\Model\Admin\SysAdminSessionsModel;
use Psr\SimpleCache\CacheInterface;

class Kickout
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(int $id): bool
    {
        $id > 0 || paramError('会话ID不能为空');

        $session = $this->sessionModel->find($id);
        ! $session && paramError('会话不存在', 404);

        if ($session->status !== 1) {
            return true;
        }

        $session->status = 0;
        $session->save();

        if (! empty($session->jwt_token)) {
            $this->cache->delete(CacheKeys::authSession($session->jwt_token));
        }

        return true;
    }
}
