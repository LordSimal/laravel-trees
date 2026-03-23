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
    public function not_root(): void
    {
        $root = (new Category(['title' => 'Root node']))->makeRoot();
        $root->save();

        $node21 = new Category(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();
        $node31 = new Category(['title' => 'child 3.1']);
        $node31->prependTo($node21)->save();

        $nodes = Category::query()->notRoot()->get();
        $this->assertCount(2, $nodes);

        $node = Category::query()->notRoot()->where('title', 'child 3.1')->first();
        $this->assertEquals($node->id, $node31->id);
    }

    #[Test]
    public function parents(): void
    {
        $this->makeTree(null, 1, 3, 2, 1, 1);

        $node12211 = Category::where(['title' => 'child 1.2.2.1.1'])->first();
        $parents = $node12211->parents()->map(fn ($item) => $item->title);

        $this->assertCount(4, $parents);
        $this->assertEquals(
            [
                'Root node 1',
                'child 1.2',
                'child 1.2.2',
                'child 1.2.2.1',
            ],
            $parents->toArray()
        );

        $parents = $node12211->parents(2)->map(fn ($item) => $item->title);

        $this->assertCount(2, $parents);
        $this->assertEquals(
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
        $this->makeTree(null, 1, 3, 4);

        $node122 = Category::where(['title' => 'child 1.2.2'])->first();

        $nodes = $node122->siblings()->defaultOrder()->get()->map(fn ($item) => $item->title);
        $this->assertCount(3, $nodes);
        $this->assertEquals(
            [
                'child 1.2.1',
                'child 1.2.3',
                'child 1.2.4',
            ],
            $nodes->toArray()
        );

        $nodes = $node122->siblingsAndSelf()->defaultOrder()->get()->map(fn ($item) => $item->title);

        $this->assertCount(4, $nodes);
        $this->assertEquals(
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
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();

        $this->assertEquals($node1->id, $node2->prev()->first()->id);
        $this->assertEquals($node2->id, $node3->prev()->first()->id);
    }

    #[Test]
    public function next(): void
    {
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();

        $this->assertEquals($node2->id, $node1->next()->first()->id);
        $this->assertEquals($node3->id, $node2->next()->first()->id);
    }

    #[Test]
    public function prev_siblings(): void
    {
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();
        $node4 = Category::where(['title' => 'child 1.2.4'])->first();

        $this->assertCount(2, $node3->prevSiblings()->get());
        $this->assertCount(1, $node2->prevSiblings()->get());
        $this->assertContains($node1->id, $node3->prevSiblings()->pluck('id')->toArray());
        $this->assertContains($node2->id, $node3->prevSiblings()->pluck('id')->toArray());
        $this->assertCount(1, $node2->prevSiblings()->get());
        $this->assertCount(0, $node1->prevSiblings()->get());

        $nodes = $node4->prevSiblings()->defaultOrder()->get();
        $this->assertCount(3, $nodes);
        $this->assertEquals($node1->id, $nodes->first()->id);
        $this->assertEquals($node3->id, $nodes->last()->id);
    }

    #[Test]
    public function next_siblings(): void
    {
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();
        $node3 = Category::where(['title' => 'child 1.2.3'])->first();
        $node4 = Category::where(['title' => 'child 1.2.4'])->first();

        $this->assertCount(0, $node4->nextSiblings()->get());
        $this->assertCount(1, $node3->nextSiblings()->get());

        $this->assertEquals($node3->id, $node2->nextSiblings()->get()->first()->id);
        $this->assertEquals($node4->id, $node2->nextSiblings()->get()->last()->id);
        $this->assertCount(2, $node2->nextSiblings()->get());
        $this->assertCount(3, $node1->nextSiblings()->get());

        $nodes = $node3->nextSiblings()->defaultOrder()->get();
        $this->assertCount(1, $nodes);
        $this->assertEquals($node4->id, $nodes->first()->id);
        $this->assertEquals($node4->id, $nodes->last()->id);
    }

    #[Test]
    public function next_sibling(): void
    {
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.3'])->first();
        $node2 = Category::where(['title' => 'child 1.2.4'])->first();

        $this->assertEquals($node2->id, $node1->nextSibling()->first()->id);
        $this->assertNull($node2->nextSibling()->first());
    }

    #[Test]
    public function prev_sibling(): void
    {
        $this->makeTree(null, 1, 3, 4);

        $node1 = Category::where(['title' => 'child 1.2.1'])->first();
        $node2 = Category::where(['title' => 'child 1.2.2'])->first();

        $this->assertEquals($node1->id, $node2->prevSibling()->first()->id);
        $this->assertNull($node1->prevSibling()->first());
    }

    #[Test]
    public function leaf(): void
    {
        $this->makeTree(null, 1, 3, 4);
        $node = Category::where(['title' => 'child 1.2'])->first();

        $nodes = $node->descendants()->leaf()->defaultOrder()->get()->map(fn ($node) => $node->title);

        $this->assertCount(4, $nodes);
        $this->assertEquals(
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
        $this->makeTree(null, 1, 3, 4, 1);
        $node = Category::where(['title' => 'child 1.3'])->first();

        $nodes = $node->descendants()->leaves()->defaultOrder()->get()->map(fn ($node) => $node->title);

        $this->assertCount(4, $nodes);
        $this->assertEquals(
            [
                'child 1.3.1.1',
                'child 1.3.2.1',
                'child 1.3.3.1',
                'child 1.3.4.1',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendants()->leaves(1)->defaultOrder()->get()->map(fn ($node) => $node->title);

        $this->assertCount(0, $nodes);
    }

    #[Test]
    public function descendants(): void
    {
        $this->makeTree(null, 1, 3, 3, 1);
        /** @var Category $node */
        $node = Category::where(['title' => 'child 1.3'])->first();

        $nodes = $node->descendants()->get()->map(fn ($node) => $node->title);
        $this->assertCount(6, $nodes);
        $this->assertEquals(
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
        $this->assertCount(3, $nodes);
        $this->assertEquals(
            [
                'child 1.3.1',
                'child 1.3.2',
                'child 1.3.3',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendantsQuery(0)->get()->map(fn ($node) => $node->title);
        $this->assertCount(0, $nodes);

        $nodes = $node->descendantsQuery(1, true)->get()->map(fn ($node) => $node->title);
        $this->assertCount(4, $nodes);
        $this->assertEquals(
            [
                'child 1.3',
                'child 1.3.1',
                'child 1.3.2',
                'child 1.3.3',
            ],
            $nodes->toArray()
        );

        $nodes = $node->descendantsQuery(1, true, true)->get()->map(fn ($node) => $node->title);

        $this->assertCount(4, $nodes);
        $this->assertEquals(
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
    public function where_descendant_of(): void
    {
        $this->makeTree(null, 1, 3, 3, 1);

        $node = Category::where(['title' => 'child 1.3'])->first();
        $this->assertEquals('child 1.3', $node->title);

        $list = Category::whereDescendantOf($node)->get();
        $this->assertCount(6, $list);

        $root = $node->getRoot();
        $this->assertTrue($root->isRoot());

        $list = Category::whereDescendantOf($root)->get();
        $this->assertCount(21, $list);
    }

    #[Test]
    public function where_ancestor_of(): void
    {
        $this->makeTree(null, 1, 5, 2);

        $node11 = Category::where(['title' => 'child 1.1'])->first();
        $this->assertEquals('child 1.1', $node11->title);

        $list = Category::whereAncestorOf($node11)->get();
        $this->assertCount(1, $list, 'Should just be the root node');

        $node51 = Category::where(['title' => 'child 1.5.1'])->first();
        $this->assertEquals('child 1.5.1', $node51->title);

        $list = Category::whereAncestorOf($node51)->get();
        $this->assertCount(2, $list, 'Should be the root node and the parent node');

        $root = $node51->getRoot();
        $this->assertTrue($root->isRoot());

        $list = Category::whereAncestorOf($root)->get();
        $this->assertCount(0, $list);
    }

    #[Test]
    public function cant_create_multiple_roots(): void
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
