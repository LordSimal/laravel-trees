<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Exceptions\UniqueRootException;
use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class InsertBeforeTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function insert_before_root(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $this->expectException(UniqueRootException::class);

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.2']);
        $node21->insertBefore($modelRoot)->save();
    }

    #[Test]
    public function insert_before(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->prependTo($modelRoot)->save();
        $modelRoot->refresh();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->insertBefore($node21)->save();

        $modelRoot->refresh();
        $node21->refresh();

        $this->assertSame(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(6, $modelRoot->rightValue());

        $this->assertSame(1, $node31->levelValue());
        $this->assertEquals(2, $node31->leftValue());
        $this->assertEquals(3, $node31->rightValue());

        $this->assertSame(1, $node21->levelValue());
        $this->assertEquals(4, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());

        /** @var Category $node41 */
        $node41 = $this->model(['title' => 'child 3.1']);
        $node41->insertBefore($node21)->save();

        $modelRoot->refresh();
        $node21->refresh();
        $node31->refresh();

        $this->assertSame(0, $modelRoot->levelValue());
        $this->assertEquals(1, $modelRoot->leftValue());
        $this->assertEquals(8, $modelRoot->rightValue());

        $this->assertSame(1, $node31->levelValue());
        $this->assertEquals(2, $node31->leftValue());
        $this->assertEquals(3, $node31->rightValue());

        $this->assertSame(1, $node41->levelValue());
        $this->assertEquals(4, $node41->leftValue());
        $this->assertEquals(5, $node41->rightValue());

        $this->assertSame(1, $node21->levelValue());
        $this->assertEquals(6, $node21->leftValue());
        $this->assertEquals(7, $node21->rightValue());

        $this->assertTrue($node41->isEqualTo($node21->prev()->first()));
        $this->assertTrue($node31->isEqualTo($node41->prev()->first()));

        $this->assertNull($node31->prev()->first());
        $this->assertNull($node21->next()->first());

        $this->assertTrue($node41->isEqualTo($node31->next()->first()));
        $this->assertTrue($node21->isEqualTo($node41->next()->first()));
    }
}
