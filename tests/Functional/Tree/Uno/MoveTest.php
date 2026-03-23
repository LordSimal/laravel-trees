<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class MoveTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function move_append(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->prependTo($modelRoot)->save();

        $this->assertSame(1, $node21->levelValue());

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->prependTo($node21)->save();

        $this->assertSame(2, $node31->levelValue());
        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());

        $node21->refresh();
        $this->assertEquals(2, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());

        // Move to Root to the Beginning
        $node31->prependTo($modelRoot)->save();
        $node31->refresh();

        $this->assertSame(1, $node31->levelValue());
        $this->assertEquals(2, $node31->leftValue());
        $this->assertEquals(3, $node31->rightValue());

        $node21->refresh();
        $this->assertEquals(4, $node21->leftValue());
        $this->assertEquals(5, $node21->rightValue());

        $modelRoot->refresh();
        $this->assertTrue($modelRoot->isEqualTo($node31->parent));
        $this->assertCount(2, $modelRoot->children);

        $node31->appendTo($node21)->save();
        $node31->refresh();
        $this->assertSame(2, $node31->levelValue());
        $this->assertEquals(3, $node31->leftValue());
        $this->assertEquals(4, $node31->rightValue());

        $node21->refresh();
        $modelRoot->refresh();

        $this->assertTrue($node21->isEqualTo($node31->parent));
        $this->assertCount(1, $modelRoot->children);
        $this->assertCount(1, $node21->children);
    }
}
