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
use App\Dto\Session\SessionRequest;
use App\Model\Admin\SysAdminSessionsModel;
use Psr\SimpleCache\CacheInterface;

class BatchKickout
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(SessionRequest $request): int
    {
        $sessions = $this->sessionModel->whereIn('id', $request->ids ?? [])->get();
        $count = 0;

        foreach ($sessions as $session) {
            if ($session->status !== 1) {
                continue;
            }

            $session->status = 0;
            $session->save();

            if (! empty($session->jwt_token)) {
                $this->cache->delete(CacheKeys::authSession($session->jwt_token));
            }

            ++$count;
        }

        return $count;
    }
}
