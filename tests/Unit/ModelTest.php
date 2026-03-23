<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\Category;
use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class ModelTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function make_model(): void
    {
        $model = new Category(['title' => 'Root node']);

        static::assertFalse($model->isMulti());
        static::assertFalse($model->getTreeConfig()->isMulti());
        static::assertFalse($model->getTreeConfig()->isSoftDelete);
    }

    #[Test]
    public function make_model_multi(): void
    {
        $model = new MultiCategory(['title' => 'Root node']);

        static::assertTrue($model->isMulti());
        static::assertTrue($model->getTreeConfig()->isMulti());
        static::assertFalse($model->getTreeConfig()->isSoftDelete);
    }

    #[Test]
    public function check_casts(): void
    {
        $model = new Category(['title' => 'Root node']);
        $casts = $model->getCasts();

        static::assertEquals('integer', $casts[(string) $model->leftAttribute()]);
        static::assertEquals('integer', $casts[(string) $model->rightAttribute()]);
        static::assertEquals('integer', $casts[(string) $model->levelAttribute()]);
        static::assertEquals($model->getKeyType(), $casts[(string) $model->parentAttribute()]);
    }
}
