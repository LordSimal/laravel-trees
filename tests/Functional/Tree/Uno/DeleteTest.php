<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Exceptions\DeleteRootException;
use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class DeleteTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function delete_root(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $this->expectException(DeleteRootException::class);

        $modelRoot->delete();
    }

    #[Test]
    public function delete_node(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->prependTo($modelRoot)->save();

        $modelRoot->refresh();
        $this->assertTrue($node21->isLeaf());
        $this->assertTrue($node21->isChildOf($modelRoot));
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(4, $modelRoot->rightValue());
        $this->assertSame(1, $modelRoot->children()->count());

        $this->assertTrue($node21->delete());

        $modelRoot->refresh();
        $this->assertTrue($modelRoot->isLeaf());
        $this->assertEmpty($modelRoot->children()->count());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(2, $modelRoot->rightValue());
    }

    #[Test]
    public function delete_node_with_line_children(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $nodeToDelete */
        $nodeToDelete = $this->model(['title' => 'deletable node']);
        $nodeToDelete->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($nodeToDelete)->save();

        /** @var Category $node22 */
        $node22 = $this->model(['title' => 'child 2.2']);
        $node22->appendTo($nodeToDelete)->save();

        $modelRoot->refresh();
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(8, $modelRoot->rightValue());
        $this->assertSame(1, $modelRoot->children()->count());
        $this->assertSame(3, $modelRoot->descendants()->count());

        $nodeToDelete->deleteWithChildren();

        $modelRoot->refresh();

        $this->assertTrue($modelRoot->isLeaf());
        $this->assertEmpty($modelRoot->children()->count());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(2, $modelRoot->rightValue());
    }

    #[Test]
    public function delete_node_with_move_children_to_parent(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $nodeToDelete */
        $nodeToDelete = $this->model(['title' => 'deletable node']);
        $nodeToDelete->appendTo($modelRoot)->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($nodeToDelete)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $nodeToDelete->refresh();
        $node21->refresh();
        $node31->refresh();

        $this->assertEquals(2, $nodeToDelete->leftValue());
        $this->assertEquals(7, $nodeToDelete->rightValue());
        $this->assertEquals(1, $nodeToDelete->levelValue());

        $this->assertEquals(3, $node21->leftValue());
        $this->assertEquals(6, $node21->rightValue());
        $this->assertEquals(2, $node21->levelValue());

        $this->assertEquals(4, $node31->leftValue());
        $this->assertEquals(5, $node31->rightValue());
        $this->assertEquals(3, $node31->levelValue());

        // delete

        $nodeToDelete->delete();

        $modelRoot->refresh();

        $this->assertCount(1, $modelRoot->children()->get());
        $this->assertFalse($modelRoot->isLeaf());

        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());

        $node21->refresh();
        $this->assertTrue($modelRoot->isEqualTo($node21->parent));
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());
        $this->assertEquals(1, $node21->levelValue());

        $node31->refresh();
        $this->assertTrue($node21->isEqualTo($node31->parent));
        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());
        $this->assertEquals(2, $node31->levelValue());
    }
}
