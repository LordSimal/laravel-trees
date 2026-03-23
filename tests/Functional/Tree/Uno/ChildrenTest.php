<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class ChildrenTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function children(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        /** @var Category $node22 */
        $node22 = $this->model(['title' => 'child 2.2']);
        /** @var Category $node23 */
        $node23 = $this->model(['title' => 'child 2.3']);

        /** @var Category $node221 */
        $node221 = $this->model(['title' => 'child 2.2.1']);
        /** @var Category $node2211 */
        $node2211 = $this->model(['title' => 'child 2.2.1.1']);

        $node21->appendTo($modelRoot)->save();
        $node22->appendTo($modelRoot)->save();
        $node23->appendTo($modelRoot)->save();

        $node221->appendTo($node22)->save();
        $node2211->appendTo($node221)->save();

        $node221->refresh();
        $node21->refresh();
        $node22->refresh();
        $modelRoot->refresh();

        $this->assertCount(0, $node21->children);
        $this->assertCount(0, $node23->children);
        $this->assertCount(1, $node22->children);
        $this->assertCount(1, $node221->children()->get());
        $this->assertCount(3, $modelRoot->children);
    }

    #[Test]
    public function save_children(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);

        $modelRoot->children()->save($node21);
        $modelRoot->refresh();

        $this->assertEquals(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(4, $modelRoot->rightValue());
        $this->assertEquals(1, $node21->levelValue());
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(3, $node21->rightValue());
    }

    #[Test]
    public function create_any_children_tree(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);

        /** @var Category $node22 */
        $node22 = $this->model(['title' => 'child 2.2']);

        $modelRoot->children()->save($node21);
        $node21->children()->save($node22);

        $this->assertEquals(0, $modelRoot->levelValue());
        $this->assertEquals(1, $node21->levelValue());
        $this->assertEquals(2, $node22->levelValue());

        $modelRoot->refresh();
        $node21->refresh();
        $node22->refresh();

        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());
        $this->assertEquals(3, $node22->leftValue());
        $this->assertEquals(4, $node22->rightValue());
    }
}
