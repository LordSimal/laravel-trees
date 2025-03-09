<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use LordSimal\LaravelTrees\Config\Operation;
use LordSimal\LaravelTrees\Exceptions\TreeNeedValueException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin \LordSimal\LaravelTrees\EloquentQueryBuilder<static>
 */
trait MoveNode
{
    public function up(): bool
    {
        $prev = $this->prevSibling()->first();
        if (! $prev) {
            return false;
        }

        return $this->insertBefore($prev)->forceSave();
    }

    public function down(): bool
    {
        $next = $this->nextSibling()->first();

        if (! $next) {
            return false;
        }

        return $this->insertAfter($next)->forceSave();
    }

    /**
     * Allows insert a new node before all children nodes in the target node
     *
     * @param  TModel  $node
     * @return $this
     *
     * @example `$model->prependTo($modelRoot)->save();`
     */
    public function prependTo(Model $node): static
    {
        $this->operation = Operation::PrependTo;
        $this->node = $node;

        return $this;
    }

    /**
     * Allows insert a new node after all children nodes in the target node
     *
     * @param  TModel  $node
     * @return $this
     *
     * @example `$model->appendTo($modelRoot)->save();`
     */
    public function appendTo(Model $node): static
    {
        $this->operation = Operation::AppendTo;
        $this->node = $node;

        return $this;
    }

    /**
     * Allows insert a new node before the target node (on the same level)
     *
     * @param  TModel  $node
     * @return $this
     */
    public function insertBefore(Model $node): static
    {
        $this->operation = Operation::InsertBefore;
        $this->node = $node;

        return $this;
    }

    /**
     * Allows insert a new node after the target node (on the same level)
     *
     * @param  TModel  $node
     * @return $this
     */
    public function insertAfter(Model $node): static
    {
        $this->operation = Operation::InsertAfter;
        $this->node = $node;

        return $this;
    }

    /**
     * Move target node's children to it's parent
     */
    public function moveChildrenToParent(): void
    {
        $this->descendantsQuery()
            ->update(
                [
                    (string) $this->leftAttribute() => new Expression($this->leftAttribute().'- 1'),
                    (string) $this->rightAttribute() => new Expression($this->rightAttribute().'- 1'),
                    (string) $this->levelAttribute() => new Expression($this->levelAttribute().'- 1'),
                ]
            );

        $parent = $this->parent;

        $condition = [
            [
                (string) $this->levelAttribute(),
                '=',
                $parent->levelValue() + 1,
            ],
        ];

        $this
            ->where($condition)
            ->treeCondition()
            ->update(
                [
                    (string) $this->parentAttribute() => $parent->getKey(),
                ]
            );
    }

    /**
     * @throws \LordSimal\LaravelTrees\Exceptions\Exception
     */
    protected function moveNode(int $to, int $depth = 0): void
    {
        $left = $this->leftValue();
        $right = $this->rightValue();
        $depth = $this->levelValue() - $this->node->levelValue() - $depth;

        if (! $this->isMulti() || $this->treeValue() === $this->node->treeValue()) {
            // same root
            $this->newQuery()
                ->descendantsQuery(null, true)
                ->update(
                    [
                        (string) $this->levelAttribute() => new Expression(
                            "-{$this->levelAttribute()} + ".$depth
                        ),
                    ]
                );

            $delta = $right - $left + 1;

            if ($left >= $to) {
                $this->shift($to, $left - 1, $delta);
                $delta = $to - $left;
            } else {
                $this->shift($right + 1, $to - 1, -$delta);
                $delta = $to - $right - 1;
            }

            $this->newQuery()
                ->descendantsQuery(null, true)
                ->where((string) $this->levelAttribute(), '<', 0)
                ->update(
                    [
                        (string) $this->leftAttribute() => new Expression(
                            $this->leftAttribute().' + '.$delta
                        ),
                        (string) $this->rightAttribute() => new Expression(
                            $this->rightAttribute().' + '.$delta
                        ),
                        (string) $this->levelAttribute() => new Expression("-{$this->levelAttribute()}"),
                    ]
                );
        } else {
            // move from other root
            $tree = $this->node->treeValue();
            $this->shift($to, null, ($right - $left + 1), $tree);
            $delta = $to - $left;

            $this->newQuery()
                ->descendantsQuery(null, true)
                ->update(
                    [
                        (string) $this->leftAttribute() => new Expression(
                            $this->leftAttribute().' + '.$delta
                        ),
                        (string) $this->rightAttribute() => new Expression(
                            $this->rightAttribute().' + '.$delta
                        ),
                        (string) $this->levelAttribute() => new Expression(
                            $this->levelAttribute().' + '.-$depth
                        ),
                        (string) $this->treeAttribute() => $tree,
                    ]
                );

            $this->shift($right + 1, null, ($left - $right - 1));
        }
    }

    protected function moveNodeAsRoot(): void
    {
        $left = $this->leftValue();
        $right = $this->rightValue();
        $depth = $this->levelValue();

        if ($this->treeIdGenerator() === null) {
            throw new TreeNeedValueException();
        }

        $tree = $this->treeChange ?: $this->generateTreeId();

        $this->newQuery()
            ->descendantsQuery(null, true)
            ->update(
                [
                    (string) $this->leftAttribute() => new Expression(
                        $this->leftAttribute().' + '.(1 - $left)
                    ),
                    (string) $this->rightAttribute() => new Expression(
                        $this->rightAttribute().' + '.(1 - $left)
                    ),
                    (string) $this->levelAttribute() => new Expression(
                        $this->levelAttribute().' + '.-$depth
                    ),
                    (string) $this->treeAttribute() => $tree,
                ]
            );

        $this->shift($right + 1, null, ($left - $right - 1));
    }

    protected function shift(int $from, ?int $to, int $delta, int|string|null $tree = null): void
    {
        // todo: reformat: and test it
        // if ($delta === 0 || !($to === null || $to >= $from)) { return }
        if ($delta !== 0 && ($to === null || $to >= $from)) {
            if ($tree === null && $this->isMulti()) {
                $tree = $this->treeValue();
            }

            foreach ([(string) $this->leftAttribute(), (string) $this->rightAttribute()] as $i => $attribute) {
                $query = $this->query();
                if ($this->isMulti()) {
                    $query->where((string) $this->treeAttribute(), $tree);
                }

                if ($to !== null) {
                    $query->whereBetween($attribute, [$from, $to]);
                } else {
                    $query->where($attribute, '>=', $from);
                }

                if ($this->isSoftDelete()) {
                    $query->withTrashed();
                }

                $query->update(
                    [
                        $attribute => new Expression($attribute.'+ '.$delta),
                    ]
                );
            }
        }
    }
}
