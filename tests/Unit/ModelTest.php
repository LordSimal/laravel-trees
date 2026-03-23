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

        $this->assertFalse($model->isMulti());
        $this->assertFalse($model->getTreeConfig()->isMulti());
        $this->assertFalse($model->getTreeConfig()->isSoftDelete);
    }

    #[Test]
    public function make_model_multi(): void
    {
        $model = new MultiCategory(['title' => 'Root node']);

        $this->assertTrue($model->isMulti());
        $this->assertTrue($model->getTreeConfig()->isMulti());
        $this->assertFalse($model->getTreeConfig()->isSoftDelete);
    }

    #[Test]
    public function check_casts(): void
    {
        $model = new Category(['title' => 'Root node']);
        $casts = $model->getCasts();

        $this->assertEquals('integer', $casts[(string) $model->leftAttribute()]);
        $this->assertEquals('integer', $casts[(string) $model->rightAttribute()]);
        $this->assertEquals('integer', $casts[(string) $model->levelAttribute()]);
        $this->assertEquals($model->getKeyType(), $casts[(string) $model->parentAttribute()]);
    }
}
