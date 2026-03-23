<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class DownTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function down(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        $node21->appendTo($modelRoot)->save();

        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        $node31->appendTo($modelRoot)->save();

        /** @var Category $node41 */
        $node41 = $this->model(['title' => 'child 4.1']);
        $node41->appendTo($modelRoot)->save();

        $children = $modelRoot->children()->defaultOrder()->get()->map->title;

        $this->assertCount(3, $children);
        $this->assertEquals(['child 2.1', 'child 3.1', 'child 4.1'], $children->toArray());

        $this->assertTrue($node21->down());
        $node21->refresh();
        $node31->refresh();
        $this->assertEquals(2, $node31->leftValue());
        $this->assertEquals(4, $node21->leftValue());
        $this->assertFalse($node21->isForceSaving());

        $children = $modelRoot->children()->defaultOrder()->get()->map->title;
        $this->assertEquals(['child 3.1', 'child 2.1', 'child 4.1'], $children->toArray());

        $this->assertTrue($node21->down());
        $node21->refresh();
        $node41->refresh();
        $this->assertEquals(6, $node21->leftValue());
        $this->assertFalse($node21->isForceSaving());

        $children = $modelRoot->children()->defaultOrder()->get()->map->title;
        $this->assertEquals(['child 3.1', 'child 4.1', 'child 2.1'], $children->toArray());

        $this->assertFalse($node21->down());
        $node21->refresh();
        $this->assertEquals(6, $node21->leftValue());
        $this->assertFalse($node21->isForceSaving());

        $children = $modelRoot->children()->defaultOrder()->get()->map->title;
        $this->assertEquals(['child 3.1', 'child 4.1', 'child 2.1'], $children->toArray());
    }
}
