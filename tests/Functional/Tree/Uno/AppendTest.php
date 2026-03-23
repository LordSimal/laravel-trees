<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Exceptions\Exception;
use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class AppendTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function append(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        // Level 2
        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();
        $modelRoot->refresh();

        $this->assertSame(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(4, $modelRoot->rightValue());

        $this->assertSame(1, $node21->levelValue());
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(3, $node21->rightValue());
        $this->assertCount(1, $node21->parents());

        $_root = $node21->parent()->first();

        $this->assertTrue($_root->isRoot());
        $this->assertTrue($modelRoot->isEqualTo($_root));

        // Level 3
        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 2.2']);
        $node31->appendTo($modelRoot)->save();

        $node21->refresh();
        $modelRoot->refresh();

        $this->assertSame(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());

        $this->assertSame(1, $node21->levelValue());
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(3, $node21->rightValue());

        $this->assertSame(1, $node31->levelValue());
        $this->assertEquals(4, $node31->leftValue());
        $this->assertEquals(5, $node31->rightValue());
        $this->assertCount(1, $node31->parents());

        $this->assertTrue($node31->isLeaf());
        $this->assertTrue($node21->isLeaf());

        $_root = $node31->getRoot();

        $this->assertTrue($_root->isRoot());
        $this->assertTrue($modelRoot->isEqualTo($_root));
    }

    #[Test]
    public function append_in_sub_level(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        // Level 2
        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();

        // Level 3
        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 2.2']);
        $node31->appendTo($node21)->save();

        $node21->refresh();
        $modelRoot->refresh();

        $this->assertSame(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());

        $this->assertSame(1, $node21->levelValue());
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());

        $this->assertSame(2, $node31->levelValue());
        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());
        $this->assertCount(2, $node31->parents());

        $this->assertTrue($node31->isLeaf());
        $this->assertFalse($modelRoot->isLeaf());
        $this->assertFalse($node21->isLeaf());

        $_root = $node31->getRoot();

        $this->assertTrue($_root->isRoot());
        $this->assertTrue($modelRoot->isEqualTo($_root));
    }

    #[Test]
    public function append_to_same_exception(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $this->expectException(Exception::class);

        $modelRoot->appendTo($modelRoot)->save();
    }

    #[Test]
    public function append_to_non_exist_parent_exception(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node'])->makeRoot();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);

        $this->expectException(Exception::class);
        $node21->appendTo($modelRoot)->save();
    }

    #[Test]
    public function move_to_self_children_exception(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node'])->makeRoot();
        $modelRoot->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $node21->refresh();
        $this->assertTrue($node31->isChildOf($node21));

        $this->expectException(Exception::class);
        $node21->appendTo($node31)->save();
    }
}
