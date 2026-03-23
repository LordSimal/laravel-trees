<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional;

use LordSimal\LaravelTrees\Collection;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class CollectionTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function link_nodes(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node1 */
        $node1 = $this->model(['title' => 'node 1']);
        $node1->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($node1)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $preQueryCount = count($this->model()->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        /** @var Collection $collection */
        $collection = $this->model()::all();

        $this->assertCount(4, $collection);

        $collection->linkNodes();

        $this->assertCount(4, $collection);

        $roots = $collection->filter(fn (Category $model) => $model->isRoot());
        $this->assertCount(1, $roots);

        /** @var Category $root */
        $root = $roots->first();
        $this->assertNull($root->parent);
        $this->assertCount(1, $root->children);

        /** @var Category $expNode1 */
        $expNode1 = $root->children->first();
        $this->assertCount(1, $expNode1->children);
        $this->assertTrue($root->isEqualTo($expNode1->parent));

        /** @var Category $expNode21 */
        $expNode21 = $expNode1->children->first();
        $this->assertCount(1, $expNode21->children);
        $this->assertTrue($expNode1->isEqualTo($expNode21->parent));

        /** @var Category $expNode31 */
        $expNode31 = $expNode21->children->first();
        $this->assertCount(0, $expNode31->children);
        $this->assertTrue($expNode21->isEqualTo($expNode31->parent));

        $this->assertCount($expectedQueryCount, $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function to_tree(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node1 */
        $node1 = $this->model(['title' => 'node 1']);
        $node1->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($node1)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $preQueryCount = count($this->model()->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        /** @var Collection $collection */
        $collection = $this->model()::all();

        $this->assertCount(4, $collection);

        $treeCollection = $collection->toTree(setParentRelations: true);

        $this->assertCount(1, $treeCollection);

        $roots = $treeCollection->getRoots();
        $this->assertCount(1, $roots);

        /** @var Category $root */
        $root = $roots->first();
        $this->assertNull($root->parent);
        $this->assertCount(1, $root->children);

        /** @var Category $expNode1 */
        $expNode1 = $root->children->first();
        $this->assertCount(1, $expNode1->children);
        $this->assertTrue($root->isEqualTo($expNode1->parent));

        /** @var Category $expNode21 */
        $expNode21 = $expNode1->children->first();
        $this->assertCount(1, $expNode21->children);
        $this->assertTrue($expNode1->isEqualTo($expNode21->parent));

        /** @var Category $expNode31 */
        $expNode31 = $expNode21->children->first();
        $this->assertCount(0, $expNode31->children);
        $this->assertTrue($expNode21->isEqualTo($expNode31->parent));

        $this->assertCount($expectedQueryCount, $root->getConnection()->getQueryLog());

        $this->assertEquals(4, $treeCollection->totalCount());
    }

    #[Test]
    public function fill_missing_intermediate_nodes(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node1 */
        $node1 = $this->model(['title' => 'node 1']);
        $node1->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($node1)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        /** @var Collection $collection */
        $collection = new Collection([$node31]);

        $collection->fillMissingIntermediateNodes();

        $this->assertCount(4, $collection);
    }

    #[Test]
    public function to_breadcrumbs(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node1 */
        $node1 = $this->model(['title' => 'node 1']);
        $node1->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($node1)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        /** @var Collection $collection */
        $collection = new Collection([$node31]);

        $treeCollection = $collection->toBreadcrumbs();

        $this->assertCount(1, $treeCollection);

        $roots = $treeCollection->getRoots();
        $this->assertCount(1, $roots);

        /** @var Category $root */
        $root = $roots->first();
        $this->assertNull($root->parent);
        $this->assertCount(1, $root->children);

        /** @var Category $expNode1 */
        $expNode1 = $root->children->first();
        $this->assertCount(1, $expNode1->children);
        $this->assertTrue($root->isEqualTo($expNode1->parent));

        /** @var Category $expNode21 */
        $expNode21 = $expNode1->children->first();
        $this->assertCount(1, $expNode21->children);
        $this->assertTrue($expNode1->isEqualTo($expNode21->parent));

        /** @var Category $expNode31 */
        $expNode31 = $expNode21->children->first();
        $this->assertCount(0, $expNode31->children);
        $this->assertTrue($expNode21->isEqualTo($expNode31->parent));

        $this->assertEquals(4, $treeCollection->totalCount());
    }
}
