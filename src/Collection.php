<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees;

use Illuminate\Database\Eloquent\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Config\Helper;
use LordSimal\LaravelTrees\Exceptions\Exception;

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Support\Collection<TKey, TModel>
 */
class Collection extends BaseCollection
{
    private bool $linked = false;

    private bool $handledToTree = false;

    private int $totalCount = 0;

    protected function setToTree(int $count): static
    {
        $this->handledToTree = true;
        $this->totalCount = $count;

        return $this;
    }

    /**
     * Build a tree from a list of nodes. Each item will have set children relation.
     *
     * If `$fromNode` is provided, the tree will contain only descendants of that node.
     * If `$fillMissingIntermediateNodes` is provided, the tree will get missing intermediate nodes from database.
     *
     * @param  bool  $setParentRelations  Set `parent` into child's relations
     */
    public function toTree(Model|string|int|null $fromNode = null, bool $setParentRelations = false): static
    {
        if ($this->handledToTree) {
            return $this;
        }

        if ($this->isEmpty()) {
            return $this;
        }

        $this->linkNodes($setParentRelations);

        $items = [];

        if ($fromNode instanceof Model) {
            $fromNode = $fromNode->getKey();
        }

        /** @var \Illuminate\Database\Eloquent\Model|\LordSimal\LaravelTrees\Traits\UseConfigShorter $node */
        foreach ($this->items as $node) {
            if ($node->parentValue() === $fromNode) {
                $items[] = $node;
            }
        }

        return (new self($items))->setToTree($this->count());
    }

    /**
     * Fill `parent` and `children` relationships for every node in the collection.
     *
     * This will overwrite any previously set relations.
     *
     * To avoid unnecessary requests to db
     *
     * @param  bool  $setParentRelations  Set `parent` into child's relations
     */
    public function linkNodes(bool $setParentRelations = true): static
    {
        if ($this->linked) {
            return $this;
        }

        if ($this->isEmpty()) {
            return $this;
        }

        $model = $this->first();
        if (! Helper::isTreeNode($model)) {
            throw new Exception('Model should be a Tree Node');
        }

        /** @var \LordSimal\LaravelTrees\Traits\UseTree $model */
        $groupedNodes = $this->groupBy($model->parentAttribute());

        /** @var \LordSimal\LaravelTrees\Traits\UseTree|\Illuminate\Database\Eloquent\Model $node */
        foreach ($this->items as $node) {
            if (! $node->parentValue()) {
                $node->setRelation('parent', null);
            }

            $children = $groupedNodes->get($node->getKey(), []);
            if ($setParentRelations) {
                /** @var \LordSimal\LaravelTrees\Traits\UseTree|\Illuminate\Database\Eloquent\Model $child */
                foreach ($children as $child) {
                    $child->setRelation('parent', $node);
                }
            }

            $node->setRelation('children', static::make($children));
        }

        $this->linked = true;

        return $this;
    }

    /**
     * Returns all root-nodes
     */
    public function getRoots(): static
    {
        return $this->filter(static fn (Model $item) => $item->parentValue() === null);
    }

    public function totalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * Add items that are not in the collection but are intermediate nodes
     */
    public function fillMissingIntermediateNodes(): void
    {
        $nodeIds = $this->pluck('id', 'id')->all();
        $collection = $this->sortByDesc(static fn ($item) => $item->levelValue());

        /** @var \Illuminate\Database\Eloquent\Model|\LordSimal\LaravelTrees\Traits\UseTree $node */
        foreach ($collection as $node) {
            if (! $node instanceof Model || $node->isRoot() || isset($nodeIds[$node->parentValue()])) {
                continue;
            }

            /** @var \LordSimal\LaravelTrees\Collection $parents */
            $parents = $node->parentsBuilder()
                ->whereNotIn($node->getKeyName(), $nodeIds)
                ->get();

            $this->items = array_merge($this->items, $parents->all());
            $nodeIds = array_merge($parents->pluck('id', 'id')->all(), $nodeIds);
        }
    }

    /**
     * @return $this
     */
    public function toBreadcrumbs(Model|string|int|null $fromNode = null): static
    {
        $this->fillMissingIntermediateNodes();

        return $this->toTree($fromNode);
    }
}
