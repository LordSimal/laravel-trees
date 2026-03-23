<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class ParentsTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function parent(): void
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
        $node22->refresh();
        $modelRoot->refresh();

        $this->assertTrue($node221->isEqualTo($node2211->parent));
        $this->assertTrue($node22->isEqualTo($node221->parent));
        $this->assertTrue($modelRoot->isEqualTo($node22->parent));
    }

    #[Test]
    public function parents(): void
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
        $node22->refresh();
        $modelRoot->refresh();

        // all of parents
        $parents = $node2211->parents();

        $this->assertEquals(['root node', 'child 2.2', 'child 2.2.1'], $parents->map->title->toArray());
        $this->assertCount(3, $parents);

        // parents from 1 level
        $parents2 = $node2211->parents(1);

        $this->assertEquals(['child 2.2', 'child 2.2.1'], $parents2->map->title->toArray());
        $this->assertCount(2, $parents2);

        // get 1 parent from 1 level

        /** @var Category $parent */
        $parent = $node2211->parentByLevel(1);

        $this->assertTrue($node22->isEqualTo($parent));
    }
}
