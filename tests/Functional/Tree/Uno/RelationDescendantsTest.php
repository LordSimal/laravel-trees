<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class RelationDescendantsTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function descendants(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        /** @var Category $node21 */
        $node21 = $this->model(['title' => 'child 2.1']);
        /** @var Category $node31 */
        $node31 = $this->model(['title' => 'child 3.1']);
        /** @var Category $node41 */
        $node41 = $this->model(['title' => 'child 4.1']);
        /** @var Category $node32 */
        $node32 = $this->model(['title' => 'child 3.2']);
        /** @var Category $node321 */
        $node321 = $this->model(['title' => 'child 3.2.1']);

        $node21->appendTo($modelRoot)->save();
        $node31->appendTo($modelRoot)->save();
        $node41->appendTo($modelRoot)->save();
        $node32->appendTo($node31)->save();
        $node321->appendTo($node32)->save();

        $modelRoot->refresh();

        $list = $modelRoot->descendants();
        $this->assertEquals(5, $list->count());
    }
}
