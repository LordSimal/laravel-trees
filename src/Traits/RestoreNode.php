<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin \LordSimal\LaravelTrees\EloquentQueryBuilder<static>
 */
trait RestoreNode
{
    protected static ?Closure $customRestoreWithDescendantsFn = null;

    protected static ?Closure $customRestoreWithParentsFn = null;

    public function restoreWithParents(?string $deletedAt = null): mixed
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $result = static::getCustomRestoreWithParentsFn($this, $deletedAt);

        $this->exists = true;

        $this->fireModelEvent('restored', false);

        return $result;
    }

    protected static function getCustomRestoreWithParentsFn(Model $model, ?string $deletedAt = null): mixed
    {
        if ($fn = static::$customRestoreWithParentsFn) {
            return $fn($model, $deletedAt);
        }

        return static::restoreParents($model, $deletedAt);
    }

    /**
     * Restore the descendants.
     */
    protected static function restoreParents(Model $model, ?string $deletedAt = null): string|int|null
    {
        $query = $model->newNestedSetQuery()
            ->parents(null, true);

        if ($deletedAt) {
            $query->where($model->getDeletedAtColumn(), '>=', $deletedAt);
        }

        $result = $query->restore();

        return $result ? $model->getKey() : null;
    }

    public function restoreWithDescendants(?string $deletedAt = null): mixed
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $result = static::getCustomRestoreWithDescendantsFn($this, $deletedAt);

        $this->exists = true;

        $this->fireModelEvent('restored', false);

        return $result;
    }

    protected static function getCustomRestoreWithDescendantsFn(Model $model, ?string $deletedAt = null): mixed
    {
        if ($fn = static::$customRestoreWithDescendantsFn) {
            return $fn($model, $deletedAt);
        }

        return static::restoreDescendants($model, $deletedAt);
    }

    protected static function restoreDescendants(Model $model, ?string $deletedAt = null): string|int|null
    {
        $query = $model->newNestedSetQuery()
            ->descendantsQuery(null, true);

        if ($deletedAt) {
            $query->where($model->getDeletedAtColumn(), '>=', $deletedAt);
        }

        $result = $query->restore();

        return $result ? $model->getKey() : null;
    }
}
