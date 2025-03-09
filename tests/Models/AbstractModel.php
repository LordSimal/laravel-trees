<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Traits\UseTree;

/**
 * @property int $lft
 * @property int $rgt
 * @property int $lvl
 * @property ?static $parent
 * @property ?int $parent_id
 * @property static[] $children
 * @property int|string $id
 * @property array $path
 * @property array $params
 */
abstract class AbstractModel extends Model
{
    /** @use UseTree<AbstractModel> */
    use UseTree;

    protected $casts = [
        'path' => 'array',
        'params' => 'array',
    ];

    public $timestamps = false;
}
