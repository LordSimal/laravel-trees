<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Traits;

use LordSimal\LaravelTrees\Config\Operation;
use LordSimal\LaravelTrees\Exceptions\Exception;
use LordSimal\LaravelTrees\Strategy\DeleteStrategy;

trait DeleteNode
{
    /**
     * Remove target node's children
     */
    public function removeDescendants(): void
    {
        $this->newNestedSetQuery()->descendantsQuery()->delete();
    }

    public function deleteWithChildren(bool $forceDelete = true): mixed
    {
        $this->operation = Operation::DeleteAll;

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        if ($this->isSoftDelete()) {
            $this->forceDeleting = $forceDelete;
        }

        $remover = static::resolveDeleterWithChildren($this->getTreeConfig()->deleterWithChildren);
        $result = $remover->handle($this, $forceDelete);

        $this->fireModelEvent('deleted', false);

        return $result;
    }

    protected static function resolveDeleterWithChildren(string $value): DeleteStrategy
    {
        $remover = instance($value);
        if (! $remover instanceof DeleteStrategy) {
            throw new Exception('Invalid Delete Strategy for `deleteWithChildren`');
        }

        return $remover;
    }
}
