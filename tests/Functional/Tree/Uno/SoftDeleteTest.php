<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Exceptions\DeleteRootException;
use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\ArchivedCategory;
use PHPUnit\Framework\Attributes\Test;

class SoftDeleteTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<ArchivedCategory>
     */
    protected function modelClass(): string
    {
        return ArchivedCategory::class;
    }

    #[Test]
    public function delete_root(): void
    {
        /** @var ArchivedCategory $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $this->expectException(DeleteRootException::class);

        $modelRoot->delete();
    }

    #[Test]
    public function delete_node(): void
    {
        /** @var ArchivedCategory $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var ArchivedCategory $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();

        $modelRoot->refresh();
        $this->assertTrue($node21->isLeaf());
        $this->assertTrue($node21->isChildOf($modelRoot));
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(4, $modelRoot->rightValue());
        $this->assertSame(1, $modelRoot->children()->count());

        // soft delete node
        $this->assertTrue($node21->delete());

        $modelRoot->refresh();

        $this->assertTrue($modelRoot->isLeaf());
        $this->assertEmpty($modelRoot->children()->count());
        $this->assertEmpty($modelRoot->descendants()->count());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(4, $modelRoot->rightValue());

        // children with Trashed nodes
        $this->assertEquals(1, $modelRoot->children()->withTrashed()->count());
        $this->assertEquals(1, $modelRoot->childrenWithTrashed()->count());
        $this->assertEquals(1, $modelRoot->childrenWithTrashed->count());
    }

    #[Test]
    public function delete_node_with_children(): void
    {
        /** @var ArchivedCategory $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var ArchivedCategory $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();

        /** @var ArchivedCategory $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $modelRoot->refresh();
        $node21->refresh();
        $this->assertFalse($node21->isLeaf());
        $this->assertTrue($node21->isChildOf($modelRoot));
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());
        $this->assertSame(1, $modelRoot->children()->count());
        $this->assertSame(2, $modelRoot->descendants()->count());

        $this->assertNull($node21->{$node21->getDeletedAtColumn()});

        // soft delete node
        $this->assertTrue($node21->delete());

        $modelRoot->refresh();

        $this->assertTrue($modelRoot->isLeaf());
        $this->assertEmpty($modelRoot->children()->count());
        $this->assertEquals(1, $modelRoot->descendants()->count());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());

        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());
        $this->assertEquals(1, $node21->levelValue());
        $this->assertNotNull($node21->{$node21->getDeletedAtColumn()});

        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());
        $this->assertEquals(2, $node31->levelValue());
        $this->assertNull($node31->{$node31->getDeletedAtColumn()});

        // children with Trashed nodes
        $this->assertEquals(1, $modelRoot->children()->withTrashed()->count());
        $this->assertEquals(1, $modelRoot->childrenWithTrashed()->count());
        $this->assertEquals(1, $modelRoot->childrenWithTrashed->count());
        $this->assertEquals(2, $modelRoot->descendants()->withTrashed()->count());

        // Recover Node

        $node21->restore();

        $modelRoot->refresh();
        $node21->refresh();

        $this->assertFalse($node21->isLeaf());
        $this->assertTrue($node21->isChildOf($modelRoot));
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());
        $this->assertSame(1, $modelRoot->children()->count());
        $this->assertSame(2, $modelRoot->descendants()->count());

        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());
        $this->assertEquals(1, $node21->levelValue());

        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());
        $this->assertEquals(2, $node31->levelValue());
    }
}
