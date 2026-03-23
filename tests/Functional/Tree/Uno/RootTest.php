<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Exceptions\NotSupportedException;
use LordSimal\LaravelTrees\Exceptions\UniqueRootException;
use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class RootTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function create_root(): void
    {
        /** @var Category $model */
        $model = $this->model(['title' => 'root node']);

        $model->makeRoot()->save();

        $this->assertSame(1, $model->id);
        $this->assertTrue($model->isRoot());

        $this->assertNotNull($model->getRoot());
        $this->assertInstanceOf(static::modelClass(), $model->getRoot());

        $this->assertEquals($model->id, $model->getRoot()->id);
        $this->assertEquals($model->title, $model->getRoot()->title);
        $this->assertEquals(1, $model->leftValue());
        $this->assertEquals(2, $model->rightValue());
        $this->assertEquals($model->lvl, $model->getRoot()->lvl);
        $this->assertSame(0, $model->getRoot()->lvl);

        $this->assertEmpty($model->parents());
        $this->assertTrue($model->isLeaf());
    }

    #[Test]
    public function create_several_root(): void
    {
        /** @var Category $model */
        $model = $this->model(['title' => 'root 1']);
        $model->makeRoot()->save();

        $this->expectException(UniqueRootException::class);

        $model = $this->model(['title' => 'root 2']);
        $model->makeRoot()->save();
    }

    #[Test]
    public function base_save_exception(): void
    {
        $model = $this->model(['id' => 2, 'title' => 'node']);
        $this->expectException(NotSupportedException::class);
        $model->save();
    }
}
