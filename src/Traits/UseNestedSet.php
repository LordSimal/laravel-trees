<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Traits;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Config\Helper;
use LordSimal\LaravelTrees\Config\Operation;
use LordSimal\LaravelTrees\Exceptions\DeletedNodeHasChildrenException;
use LordSimal\LaravelTrees\Exceptions\DeleteRootException;
use LordSimal\LaravelTrees\Exceptions\Exception;
use LordSimal\LaravelTrees\Exceptions\NotSupportedException;
use LordSimal\LaravelTrees\Exceptions\TreeNeedValueException;
use LordSimal\LaravelTrees\Exceptions\UniqueRootException;
use LordSimal\LaravelTrees\Generators\GeneratorTreeId;
use LordSimal\LaravelTrees\Generators\GeneratorTreeIdContract;
use LordSimal\LaravelTrees\Strategy\ChildrenHandler;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin \LordSimal\LaravelTrees\EloquentQueryBuilder<static>
 */
trait UseNestedSet
{
    use DeleteNode;

    /** @use \LordSimal\LaravelTrees\Traits\MoveNode<TModel> */
    use MoveNode;

    /** @use \LordSimal\LaravelTrees\Traits\MoveNode<TModel> */
    use RestoreNode;

    use WithQueryBuilder;
    use WithRelations;

    /**
     * @var \Illuminate\Database\Eloquent\Model|\LordSimal\LaravelTrees\Traits\UseTree|null
     */
    protected ?Model $node = null;

    protected int|string|null $treeChange = null;

    protected ?Operation $operation = null;

    protected bool $forceSave = false;

    protected static function bootUseNestedSet(): void
    {
        static::creating(fn (self $model) => $model->beforeInsert());
        static::created(fn (self $model) => $model->afterInsert());
        static::updating(fn (self $model) => $model->beforeUpdate());
        static::updated(fn (self $model) => $model->afterUpdate());
        static::saving(fn (self $model) => $model->beforeSave());
        static::deleting(fn (self $model) => $model->beforeDelete());

        static::deleted(
            static function ($model): void {
                /** @var static|TModel $model */
                if ($model->isSoftDelete() && ! $model->isForceDeleting()) {
                    return;
                }

                $model->afterDelete();
            }
        );

        if (Helper::isModelSoftDeletable(static::class)) {
            static::restoring(fn (self $model) => $model->beforeRestore());
            static::restored(fn (self $model) => $model->afterRestore());
        }
    }

    /** ====================== START Event Handlers ====================== */
    protected function beforeInsert(): void
    {
        $this->nodeRefresh();

        if (! $this->operation) {
            if ($parent = $this->parentWithTrashed) {
                $this->operation = Operation::AppendTo;
                $this->node = $parent;
            } else {
                if ($this->isMulti() || $this->getAttributeFromArray('_setRoot')) {
                    $this->operation = Operation::MakeRoot;
                    unset($this->attributes['_setRoot']);
                }
            }
        }

        switch ($this->operation) {
            case Operation::MakeRoot:
                if (! $this->isMulti() && ($exist = $this->root()->first()) !== null) {
                    throw new UniqueRootException($exist);
                }

                $this->validateAndSetTreeId();

                $this->setAttribute((string) $this->leftAttribute(), 1);
                $this->setAttribute((string) $this->rightAttribute(), 2);
                $this->setAttribute((string) $this->levelAttribute(), 0);

                break;

            case Operation::PrependTo:
                $this->validateExists();
                $this->insertNode($this->node->leftValue() + 1, 1);
                break;
            case Operation::AppendTo:
                $this->validateExists();
                $this->insertNode($this->node->rightValue(), 1);
                break;
            case Operation::InsertBefore:
                $this->validateExists();
                $this->insertNode($this->node->leftValue());
                break;
            case Operation::InsertAfter:
                $this->validateExists();
                $this->insertNode($this->node->rightValue() + 1);
                break;
            default:
                throw new NotSupportedException(
                    null,
                    sprintf('Method "%s::insert" is not supported for inserting new nodes.', $this::class)
                );
        }
    }

    protected function afterInsert(): void
    {
        $this->operation = null;
        $this->node = null;
    }

    protected function beforeUpdate(): void
    {
        $this->nodeRefresh();

        switch ($this->operation) {
            case Operation::MakeRoot:
                if (! $this->isMulti()) {
                    throw new Exception('Can not move a node as the root when Model is not set to "MultiTree"');
                }

                if ($this->getOriginal((string) $this->treeAttribute()) !== ($newTreeValue = $this->treeValue())) {
                    $this->treeChange = $newTreeValue;
                    $this->setAttribute(
                        (string) $this->treeAttribute(),
                        $this->getOriginal((string) $this->treeAttribute())
                    );
                }
                break;

            case Operation::InsertBefore:
            case Operation::InsertAfter:
                if (! $this->isMulti() && $this->node->isRoot()) {
                    throw new UniqueRootException(
                        $this->node,
                        'Can not move a node before/after root. Model must be "MultiTree"'
                    );
                }
                break;

            case Operation::PrependTo:
            case Operation::AppendTo:
                if ($this->isEqualTo($this->node)) {
                    throw new Exception('Can not move a node when the target node is same.');
                }

                if ($this->node->isChildOf($this)) {
                    throw new Exception('Can not move a node when the target node is child.');
                }
                break;
        }
    }

    protected function afterUpdate(): void
    {
        switch ($this->operation) {
            case Operation::MakeRoot:
                if ($this->treeChange || $this->exists || ! $this->isRoot()) {
                    $this->moveNodeAsRoot();
                }
                break;
            case Operation::PrependTo:
                $this->moveNode($this->node->leftValue() + 1, 1);
                break;
            case Operation::AppendTo:
                $this->moveNode($this->node->rightValue(), 1);
                break;
            case Operation::InsertBefore:
                $this->moveNode($this->node->leftValue());
                break;
            case Operation::InsertAfter:
                $this->moveNode($this->node->rightValue() + 1);
                break;
        }

        $this->operation = null;
        $this->node = null;
        $this->treeChange = null;
        $this->forceSave = false;
    }

    protected function beforeSave(): void
    {
        switch ($this->operation) {
            case Operation::PrependTo:
            case Operation::AppendTo:
                $this->setAttribute((string) $this->parentAttribute(), $this->node->getKey());
                break;
            case Operation::InsertBefore:
            case Operation::InsertAfter:
                $this->setAttribute((string) $this->parentAttribute(), $this->node->parentValue());
                break;
            default:
                // Let every other operation pass
                break;
        }
    }

    protected function beforeDelete(): void
    {
        if ($this->operation !== Operation::DeleteAll && $this->isRoot()) {
            $this->onDeletingRootNode();
        }

        if (! $this->isSoftDelete() && $this->children()->count() > 0) {
            $this->onDeletingNodeHasChildren();
        }

        // We will need fresh data to delete node safely
        $this->refresh();
    }

    /**
     * If deleted node has children - these will be moved children to parent node of deleted node
     */
    protected function afterDelete(): void
    {
        $left = $this->leftValue();
        $right = $this->rightValue();

        if ($this->operation === Operation::DeleteAll || $this->isLeaf()) {
            $this->shift($right + 1, null, ($left - $right - 1));
        } else {
            $handler = static::resolveChildrenHandler($this->getTreeConfig()->childrenHandlerOnDelete);
            $handler->handle($this);

            $this->shift($right + 1, null, -2);
        }

        $this->operation = null;
        $this->node = null;
    }

    public function beforeRestore(): void
    {
        $this->operation = Operation::RestoreSelfOnly;
    }

    public function afterRestore(): void
    {
        $this->operation = null;
        $this->node = null;
        $this->treeChange = null;

        if ($this->forceSave) {
            $this->forceSave = false;
        }
    }

    /** ====================== END Event Handlers ====================== */
    protected function onDeletingRootNode(): void
    {
        if ($this->children()->count() > 0) {
            throw new DeletedNodeHasChildrenException($this);
        }

        if (! $this->isMulti()) {
            throw new DeleteRootException($this);
        }
    }

    /**
     * Callback on deleting node which has children
     */
    protected function onDeletingNodeHasChildren(): void
    {
        // throw new DeletedNodeHasChildrenException($this);
    }

    protected static function resolveChildrenHandler(string $value): ChildrenHandler
    {
        $remover = class_exists($value) ? new $value : null;
        if (! $remover instanceof ChildrenHandler) {
            throw new Exception('Invalid ChildrenHandler for `delete`');
        }

        return $remover;
    }

    public function isMulti(): bool
    {
        if ($this->node !== null) {
            return $this->node->getTreeConfig()->isMulti();
        }

        return $this->getTreeConfig()->isMulti();
    }

    public function makeRoot(): static
    {
        $this->operation = Operation::MakeRoot;

        return $this;
    }

    public function saveAsRoot(): bool
    {
        if ($this->exists && $this->isRoot()) {
            return $this->save();
        }

        return $this->makeRoot()->save();
    }

    public function forceSave(): bool
    {
        $this->forceSave = true;

        return $this->save();
    }

    public function isForceSaving(): bool
    {
        return $this->forceSave;
    }

    public function getDirty(): array
    {
        $dirty = parent::getDirty();

        if (! $dirty && $this->forceSave) {
            $dirty[(string) $this->parentAttribute()] = $this->parentValue();
        }

        return $dirty;
    }

    protected function validateAndSetTreeId(): void
    {
        if (! $this->isMulti() || $this->treeValue() !== null) {
            return;
        }

        if ($this->treeIdGenerator() !== null) {
            $this->setTree($this->generateTreeId());

            return;
        }

        throw new TreeNeedValueException();
    }

    protected function treeIdGenerator(): ?string
    {
        return GeneratorTreeId::class;
    }

    protected function generateTreeId(): string|int
    {
        $treeGeneratorClass = $this->treeIdGenerator();
        $generator = class_exists($treeGeneratorClass) ? new $treeGeneratorClass($this->treeAttribute()) : null;
        if ($generator instanceof GeneratorTreeIdContract) {
            return $generator->generateId($this);
        }

        throw new Exception('Invalid Generator');
    }

    public function setTree(string|int $treeId): static
    {
        if (! $this->isMulti()) {
            throw new Exception('Model does not implement MultiTree');
        }

        $this->setAttribute((string) $this->treeAttribute(), $treeId);

        return $this;
    }

    protected function nodeRefresh(): void
    {
        if ($this->node?->exists) {
            $this->node->refresh();
        }
    }

    protected function validateExists(): void
    {
        if (! $this->node->exists) {
            throw new Exception('Can not manipulate a node when the target node is a new record.');
        }
    }

    /**
     * @param  int  $to  Left attribute
     *
     * @throws \LordSimal\LaravelTrees\Exceptions\UniqueRootException|\LordSimal\LaravelTrees\Exceptions\Exception
     */
    protected function insertNode(int $to, int $depth = 0): void
    {
        if ($depth === 0 && $this->node->isRoot()) {
            throw new UniqueRootException($this->node, 'Can not insert a node before/after root.');
        }

        $this->setAttribute((string) $this->leftAttribute(), $to);
        $this->setAttribute((string) $this->rightAttribute(), $to + 1);
        $this->setAttribute((string) $this->levelAttribute(), $this->node->levelValue() + $depth);

        if ($this->isMulti() || ($depth > 0 && $this->node->isMulti())) {
            $this->setAttribute((string) $this->treeAttribute(), $this->node->treeValue());
        }

        $this->shift($to, null, 2);
    }

    public function trace(): array
    {
        return [
            'left' => $this->leftValue(),
            'right' => $this->rightValue(),
            'level' => $this->levelValue(),
            'tech' => [
                'forceSave' => $this->forceSave,
                'operation' => $this->operation,
                'node' => $this->node?->getKey(),
            ],
        ];
    }
}
