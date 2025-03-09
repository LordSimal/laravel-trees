<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Relations;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\EloquentQueryBuilder;

/**
 * Class AncestorsRelation
 */
class AncestorsRelation extends BaseRelation
{
    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints(): void
    {
        if (! static::$constraints) {
            return;
        }

        $this->query->whereAncestorOf($this->parent)->applyNestedSetScope();
    }

    protected function addEagerConstraint(EloquentQueryBuilder $query, Model $model): void
    {
        $query->whereAncestorOf($model);
    }

    /**
     * @return mixed
     */
    protected function matches(Model $model, Model $related): bool
    {
        return $related->isChildOf($model);
    }

    protected function relationExistenceCondition(string $hash, string $table, string $lft, string $rgt): string
    {
        return "$hash.$lft between $table.$lft + 1 and $table.$rgt";
    }
}
