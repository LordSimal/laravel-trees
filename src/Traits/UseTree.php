<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Traits;

use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Config\Config;
use LordSimal\LaravelTrees\Config\FieldType;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @method static static byTree(int|string $treeId)
 * @method static static root()
 *
 * @mixin \LordSimal\LaravelTrees\EloquentQueryBuilder<static>
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait UseTree
{
    use UseConfigShorter;

    /** @use \LordSimal\LaravelTrees\Traits\UseNestedSet<TModel> */
    use UseNestedSet;

    private Config $tree_config__;

    public function initializeUseTree(): void
    {
        $this->rebuildTreeConfig();
        $this->mergeTreeCasts();
    }

    protected function mergeTreeCasts(): void
    {
        $casts = [
            (string) $this->levelAttribute() => 'integer',
            (string) $this->leftAttribute() => 'integer',
            (string) $this->rightAttribute() => 'integer',
        ];

        $casts[(string) $this->parentAttribute()] = $this->getKeyType();

        if (($treeAttr = $this->treeAttribute())) {
            $casts[(string) $treeAttr] = $treeAttr->type()->toModelCast();
        }

        $this->mergeCasts($casts);
    }

    public function getTreeBuilder(): Builder
    {
        $builder = static::buildTree();
        $builder->parent()->setType(FieldType::fromString($this->getKeyType()));

        return $builder;
    }

    public function getTreeConfig(): Config
    {
        return $this->tree_config__ ??= $this->getTreeBuilder()->build($this);
    }

    protected function rebuildTreeConfig(): void
    {
        $this->tree_config__ = $this->getTreeBuilder()->build($this);
    }

    protected static function buildTree(): Builder
    {
        return Builder::default();
    }
}
