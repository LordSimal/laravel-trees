<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Relations;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use LordSimal\LaravelTrees\Collection;
use LordSimal\LaravelTrees\Config\Helper;
use LordSimal\LaravelTrees\EloquentQueryBuilder;

/**
 * Class BaseRelation
 *
 * @property \LordSimal\LaravelTrees\EloquentQueryBuilder $query
 */
abstract class BaseRelation extends Relation
{
    public function __construct(EloquentQueryBuilder $builder, Model $parent)
    {
        if (! Helper::isTreeNode($parent)) {
            throw new InvalidArgumentException('Model must be a node.');
        }

        parent::__construct($builder, $parent);
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  string  $relation
     */
    public function initRelation(array $models, $relation): array
    {
        return $models;
    }

    /**
     * Get the results of the relationship.
     */
    public function getResults(): Collection
    {
        return $this->query->get();
    }

    /**
     * Set the constraints for an eager load of the relation.
     */
    public function addEagerConstraints(array $models): void
    {
        // The first model in the array is always the parent, so add the scope constraints based on that model.
        // @link https://github.com/laravel/framework/pull/25240
        // @link https://github.com/lazychaser/laravel-nestedset/issues/351
        optional($models[0])->applyNestedSetScope($this->query);
        $this->query->whereNested(
            function (Builder $inner) use ($models): void {
                // We will use this query in order to apply constraints to the
                // base query builder
                $outer = $this->parent->newQuery()->setQuery($inner);
                foreach ($models as $model) {
                    $this->addEagerConstraint($outer, $model);
                }
            }
        );
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array<\Illuminate\Database\Eloquent\Model>  $models
     * @param  string  $relation
     */
    public function match(array $models, EloquentCollection $results, $relation): array
    {
        foreach ($models as $model) {
            $related = $this->matchForModel($model, $results);
            $model->setRelation($relation, $related);
        }

        return $models;
    }

    protected function matchForModel(Model $model, EloquentCollection $results): EloquentCollection
    {
        $result = $this->related->newCollection();
        foreach ($results as $related) {
            if ($this->matches($model, $related)) {
                $result->push($related);
            }
        }

        return $result;
    }

    abstract protected function addEagerConstraint(EloquentQueryBuilder $query, Model $model): void;

    abstract protected function matches(Model $model, Model $related): bool;

    abstract protected function relationExistenceCondition(
        string $hash,
        string $table,
        string $lft,
        string $rgt
    ): string;
}
