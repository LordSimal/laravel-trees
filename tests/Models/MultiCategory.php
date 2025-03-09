<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

/**
 * @property string $title
 */
class MultiCategory extends AbstractMultiModel
{
    protected $fillable = ['title'];

    protected $table = 'categories_multi';
}
