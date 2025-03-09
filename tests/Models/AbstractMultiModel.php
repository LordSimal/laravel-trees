<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Traits\UseTree;

/**
 * @property int $lft
 * @property int $rgt
 * @property int $lvl
 * @property \LordSimal\LaravelTrees\Tests\Models\AbstractMultiModel|null $parent
 * @property int $tree_id
 * @property int|string $id
 * @property array $path
 * @property array $params
 */
abstract class AbstractMultiModel extends Model
{
    use UseTree;

    protected $casts = [
        'path' => 'array',
        'params' => 'array',
    ];

    public $timestamps = false;

    protected static function buildTree(): Builder
    {
        return Builder::defaultMulti();
    }
}
