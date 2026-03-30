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

namespace App\Common\Utils\Timestamp;

use Hyperf\Context\Context;
use Throwable;

/**
 * 时间戳处理器 - 适配 Hyperf.
 */
class TimestampHandler
{
    protected static array $columnCache = [];

    public static function freshTimestamp(): int
    {
        return time() * 1000;
    }

    public static function handleCreating(object $model): void
    {
        $autoFillCreate = true;
        if (method_exists($model, 'getAutoFillCreate')) {
            $autoFillCreate = $model->getAutoFillCreate();
        }

        if (! $autoFillCreate) {
            return;
        }

        $adminId = static::getCurrentUserId();
        if (! $adminId) {
            return;
        }

        $fillable = $model->getFillable();
        $hasCreateBy = in_array('create_by', $fillable) || static::columnExists($model, 'create_by');
        $hasUpdateBy = in_array('update_by', $fillable) || static::columnExists($model, 'update_by');

        if ($hasCreateBy && ! isset($model->create_by)) {
            $model->create_by = $adminId;
        }
        if ($hasUpdateBy && ! isset($model->update_by)) {
            $model->update_by = $adminId;
        }
    }

    public static function handleUpdating(object $model): void
    {
        $autoFillUpdate = true;
        if (method_exists($model, 'getAutoFillUpdate')) {
            $autoFillUpdate = $model->getAutoFillUpdate();
        }

        if (! $autoFillUpdate) {
            return;
        }

        $adminId = static::getCurrentUserId();
        if (! $adminId) {
            return;
        }

        $fillable = $model->getFillable();
        $hasUpdateBy = in_array('update_by', $fillable) || static::columnExists($model, 'update_by');

        if ($hasUpdateBy) {
            $model->update_by = $adminId;
        }
    }

    protected static function getCurrentUserId(): ?int
    {
        try {
            $userInfo = Context::get('auth_user');
            return $userInfo['id'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function columnExists(object $model, string $column): bool
    {
        try {
            $table = $model->getTable();
            $cacheKey = "{$table}.{$column}";

            if (isset(static::$columnCache[$cacheKey])) {
                return static::$columnCache[$cacheKey];
            }

            if (method_exists($model, 'getConnection')) {
                $connection = $model->getConnection();
                $exists = $connection->getSchemaBuilder()->hasColumn($table, $column);
                static::$columnCache[$cacheKey] = $exists;
                return $exists;
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }
}
