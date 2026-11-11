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
        requireId($id, '会话');

        $session = $this->sessionModel->find($id);
        ! $session && notFound('会话');

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
