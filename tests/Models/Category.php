<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

/**
 * @property string $title
 */
class Category extends AbstractModel
{
    protected $fillable = ['title'];

    protected $table = 'categories';
}
