<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Exceptions\UniqueRootException;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class NodeBuilderSingleTreeTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function notRoot(): void
    {
        $root = (new Category(['title' => 'Root node']))->makeRoot();
        $root->save();

        $node21 = new Category(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();
        $node31 = new Category(['title' => 'child 3.1']);
        $node31->prependTo($node21)->save();

        $nodes = Category::query()->notRoot()->get();
        static::assertCount(2, $nodes);

        $node = Category::query()->notRoot()->where('title', 'child 3.1')->first();
        static::assertEquals($node->id, $node31->id);
    }

    #[Test]
    public function parents(): void
    {
        static::makeTree(null, 1, 3, 2, 1, 1);

        $node12211 = Category::where(['title' => 'child 1.2.2.1.1'])->first();
        $parents = $node12211->parents()->map(fn ($item) => $item->title);

        static::assertCount(4, $parents);
        static::assertEquals(
            [
                'Root node 1',
                'child 1.2',
                'child 1.2.2',
                'child 1.2.2.1',
            ],
            $parents->toArray()
        );

        $parents = $node12211->parents(2)->map(fn ($item) => $item->title);

        static::assertCount(2, $parents);
        static::assertEquals(
            [
                'child 1.2.2',
                'child 1.2.2.1',
            ],
            $parents->toArray()
        );
    }

    #[Test]
    public function siblings(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node122 = Category::where(['title' => 'child 1.2.2'])->first();

        $nodes = $node122->siblings()->defaultOrder()->get()->map(fn ($item) => $item->title);
        static::assertCount(3, $nodes);
        static::assertEquals(
            [
                'child 1.2.1',
                'child 1.2.3',
                'child 1.2.4',
            ],
            $nodes->toArray()
        );

        $nodes = $node122->siblingsAndSelf()->defaultOrder()->get()->map(fn ($item) => $item->title);

        static::assertCount(4, $nodes);
        static::assertEquals(
            [
                'child 1.2.1',
                'child 1.2.2',
                'child 1.2.3',
                'child 1.2.4',
            ],
            $nodes->toArray()
        );
    }

    #[Test]
    public function prev(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();

        static::assertEquals($node1->id, $node2->prev()->first()->id);
        static::assertEquals($node2->id, $node3->prev()->first()->id);
    }

    #[Test]
    public function next(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();

        static::assertEquals($node2->id, $node1->next()->first()->id);
        static::assertEquals($node3->id, $node2->next()->first()->id);
    }

    #[Test]
    public function prevSiblings(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();
        $node4 = Category::where(['title' => 'child 1.2.4'])->first();

        static::assertCount(2, $node3->prevSiblings()->get());
        static::assertCount(1, $node2->prevSiblings()->get());
        static::assertContains($node1->id, $node3->prevSiblings()->pluck('id')->toArray());
        static::assertContains($node2->id, $node3->prevSiblings()->pluck('id')->toArray());
        static::assertCount(1, $node2->prevSiblings()->get());
        static::assertCount(0, $node1->prevSiblings()->get());

        $nodes = $node4->prevSiblings()->defaultOrder()->get();
        static::assertCount(3, $nodes);
        static::assertEquals($node1->id, $nodes->first()->id);
        static::assertEquals($node3->id, $nodes->last()->id);
    }

    #[Test]
    public function nextSiblings(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();
        $node4 = Category::where(['title' => 'child 1.2.4'])->first();

        static::assertCount(0, $node4->nextSiblings()->get());
        static::assertCount(1, $node3->nextSiblings()->get());

        static::assertEquals($node3->id, $node2->nextSiblings()->get()->first()->id);
        static::assertEquals($node4->id, $node2->nextSiblings()->get()->last()->id);
        static::assertCount(2, $node2->nextSiblings()->get());
        static::assertCount(3, $node1->nextSiblings()->get());

        $nodes = $node3->nextSiblings()->defaultOrder()->get();
        static::assertCount(1, $nodes);
        static::assertEquals($node4->id, $nodes->first()->id);
        static::assertEquals($node4->id, $nodes->last()->id);
    }

    #[Test]
    public function nextSibling(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.3'])->first();
        $node2 = Category::where(['title' => 'child 1.2.4'])->first();

        static::assertEquals($node2->id, $node1->nextSibling()->first()->id);
        static::assertNull($node2->nextSibling()->first());
    }

    #[Test]
    public function prevSibling(): void
    {
        static::makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();

        static::assertEquals($node1->id, $node2->prevSibling()->first()->id);
        static::assertNull($node1->prevSibling()->first());
    }

    #[Test]
    public function leaf(): void
    {
        static::makeTree(null, 1, 3, 4);
        $node = Category::where(['title' => 'child 1.2'])->first();

        $nodes = $node->descendants()->leaf()->defaultOrder()->get()->map(fn ($node) => $node->title);

        static::assertCount(4, $nodes);
        static::assertEquals(
            [
                'child 1.2.1',
                'child 1.2.2',
                'child 1.2.3',
                'child 1.2.4',
            ],
            $nodes->toArray()
        );
    }

    #[Test]
    public function leaves(): void
    {
        static::makeTree(null, 1, 3, 4, 1);
        $node = Category::where(['title' => 'child 1.3'])->first();

        $nodes = $node->descendants()->leaves()->defaultOrder()->get()->map(fn ($node) => $node->title);

        static::assertCount(4, $nodes);
        static::assertEquals(
            [
                'child 1.3.1.1',
                'child 1.3.2.1',
                'child 1.3.3.1',
                'child 1.3.4.1',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendants()->leaves(1)->defaultOrder()->get()->map(fn ($node) => $node->title);

        static::assertCount(0, $nodes);
    }

    #[Test]
    public function descendants(): void
    {
        static::makeTree(null, 1, 3, 3, 1);
        /** @var Category $node */
        $node = Category::where(['title' => 'child 1.3'])->first();

        $nodes = $node->descendants()->get()->map(fn ($node) => $node->title);
        static::assertCount(6, $nodes);
        static::assertEquals(
            [
                'child 1.3.1',
                'child 1.3.1.1',
                'child 1.3.2',
                'child 1.3.2.1',
                'child 1.3.3',
                'child 1.3.3.1',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendantsQuery(1)->get()->map(fn ($node) => $node->title);
        static::assertCount(3, $nodes);
        static::assertEquals(
            [
                'child 1.3.1',
                'child 1.3.2',
                'child 1.3.3',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendantsQuery(0)->get()->map(fn ($node) => $node->title);
        static::assertCount(0, $nodes);

        $nodes = $node->descendantsQuery(1, true)->get()->map(fn ($node) => $node->title);
        static::assertCount(4, $nodes);
        static::assertEquals(
            [
                'child 1.3',
                'child 1.3.1',
                'child 1.3.2',
                'child 1.3.3',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendantsQuery(1, true, true)->get()->map(fn ($node) => $node->title);

        static::assertCount(4, $nodes);
        static::assertEquals(
            [
                'child 1.3',
                'child 1.3.3',
                'child 1.3.2',
                'child 1.3.1',
            ],
            $nodes->toArray()
        );
    }

    #[Test]
    public function whereDescendantOf(): void
    {
        static::makeTree(null, 1, 3, 3, 1);

        $node = Category::where(['title' => 'child 1.3'])->first();
        static::assertEquals('child 1.3', $node->title);

        $list = Category::whereDescendantOf($node)->get();
        static::assertCount(6, $list);

        $root = $node->getRoot();
        static::assertTrue($root->isRoot());

        $list = Category::whereDescendantOf($root)->get();
        static::assertCount(21, $list);
    }

    #[Test]
    public function whereAncestorOf(): void
    {
        static::makeTree(null, 1, 5, 2);

        $node11 = Category::where(['title' => 'child 1.1'])->first();
        static::assertEquals('child 1.1', $node11->title);

        $list = Category::whereAncestorOf($node11)->get();
        static::assertCount(1, $list, 'Should just be the root node');

        $node51 = Category::where(['title' => 'child 1.5.1'])->first();
        static::assertEquals('child 1.5.1', $node51->title);

        $list = Category::whereAncestorOf($node51)->get();
        static::assertCount(2, $list, 'Should be the root node and the parent node');

        $root = $node51->getRoot();
        static::assertTrue($root->isRoot());

        $list = Category::whereAncestorOf($root)->get();
        static::assertCount(0, $list);
    }

    #[Test]
    public function cantCreateMultipleRoots(): void
    {
        $root = (new Category(['title' => 'Root node']))->makeRoot();
        $root->save();

        $node21 = new Category(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();

        $node31 = new Category(['title' => 'child 3.1']);
        $node31->makeRoot();

        $this->expectException(UniqueRootException::class);
        $node31->save();
    }
}
