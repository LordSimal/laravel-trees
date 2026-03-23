<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Multi;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class RootTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<MultiCategory>
     */
    protected function modelClass(): string
    {
        return MultiCategory::class;
    }

    #[Test]
    public function create_root(): void
    {
        /** @var MultiCategory $model */
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

        $this->assertEquals($model->tree_id, $model->getRoot()->tree_id);
        $this->assertSame(1, $model->getRoot()->tree_id);

        $this->assertEmpty($model->parents());
    }

    #[Test]
    public function create_several_root(): void
    {
        /** @var MultiCategory $model */
        $model = $this->model(['title' => 'root 1']);
        $model->makeRoot()->save();
        $this->assertSame(1, $model->tree_id);

        $model2 = $this->model(['title' => 'root 2']);
        $model2->makeRoot()->save();
        $this->assertSame(2, $model2->tree_id);
    }

    #[Test]
    public function create_several_root_without_mark_them_as_root(): void
    {
        /** @var MultiCategory $model */
        $model = $this->model(['title' => 'root 1']);
        $model->save();
        $this->assertSame(1, $model->tree_id);
        $this->assertEquals(1, $model->leftValue());
        $this->assertEquals(2, $model->rightValue());
        $this->assertEmpty($model->parents());
        $this->assertTrue($model->isLeaf());

        $model2 = $this->model(['title' => 'root 2']);
        $model2->save();

        $this->assertSame(2, $model2->tree_id);
        $this->assertEquals(1, $model2->leftValue());
        $this->assertEquals(2, $model2->rightValue());
        $this->assertEmpty($model2->parents());

        $this->assertNotEquals($model->tree_id, $model2->tree_id);
        $this->assertTrue($model2->isLeaf());
    }

    #[Test]
    public function receive_roots(): void
    {
        /** @var MultiCategory $root */
        $root = $this->model(['title' => 'root 1']);
        $root->saveAsRoot();
        $this->model(['title' => 'child 2.1'])->prependTo($root)->save();

        $this->model(['title' => 'root 2'])->saveAsRoot();

        $this->assertEquals(2, MultiCategory::root()->count());
    }
}
