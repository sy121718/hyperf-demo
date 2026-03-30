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

namespace App\Service\Traits;

trait CheckOwnership
{
    protected function checkOwnership(object $resource, string $ownerField = 'create_by'): void
    {
        $isSuperAdmin = currentUser('is_admin') == 1;

        if (! $isSuperAdmin) {
            $ownerId = $resource->{$ownerField} ?? null;
            $currentUserId = currentUser('id');

            if ($ownerId && $ownerId != $currentUserId) {
                paramError('无权访问此资源', 403);
            }
        }
    }

    protected function isSuperAdmin(): bool
    {
        return currentUser('is_admin') == 1;
    }

    protected function isOwnerOrSuperAdmin(object $resource, string $ownerField = 'create_by'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $ownerId = $resource->{$ownerField} ?? null;
        $currentUserId = currentUser('id');

        return $ownerId == $currentUserId;
    }
}
