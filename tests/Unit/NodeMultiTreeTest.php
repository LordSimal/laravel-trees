<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Exceptions\DeletedNodeHasChildrenException;
use LordSimal\LaravelTrees\Exceptions\Exception;
use LordSimal\LaravelTrees\Exceptions\UniqueRootException;
use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class NodeMultiTreeTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function create_root(): void
    {
        $this->makeTree(null, 1);
        /** @var MultiCategory $model */
        $model = MultiCategory::first();

        $this->assertSame(1, $model->getKey());
        $this->assertSame(1, $model->treeValue());
        $this->assertTrue($model->isRoot());

        $this->assertInstanceOf(MultiCategory::class, $model->getRoot());

        $this->assertEquals($model->id, $model->getRoot()->id);
        $this->assertEquals($model->title, $model->getRoot()->title);
        $this->assertEquals($model->lvl, $model->getRoot()->lvl);
        $this->assertEmpty($model->parents());
        $this->assertEmpty($model->children);
    }

    #[Test]
    public function create_root_auto_gen_multi_tree(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $root = $this->createNodeAutoGen($i);

            for ($j = 1; $j <= 10; $j++) {
                $this->createNodeAutoGen($j, $root);
            }
        }
    }

    private function createNodeAutoGen($no, ?Model $parent = null): Model
    {
        $model = new MultiCategory(['title' => ($parent ? 'sub' : 'root').'node #'.$no]);

        if (! $parent) {
            $model->makeRoot();
        } else {
            $model->prependTo($parent);
        }

        $model->save();

        $this->assertEmpty($model->children);
        $this->assertInstanceOf(MultiCategory::class, $model->getRoot());
        if (! $parent) {
            // root
            $this->assertTrue(is_int($model->treeValue()));
            $this->assertTrue($model->isRoot());

            $this->assertEquals($model->id, $model->getRoot()->id);
            $this->assertEquals($model->title, $model->getRoot()->title);
            $this->assertEquals($model->lvl, $model->getRoot()->lvl);
            $this->assertEmpty($model->parents());
        } else {
            // sub-nodes
            $this->assertSame($model->getRoot()->getKey(), $model->parent->getKey());
            $this->assertSame($model->parent->treeValue(), $model->treeValue());
        }

        return $model;
    }

    #[Test]
    public function insert_node(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $root = (new MultiCategory(['title' => 'root '.$i]))->makeRoot();
            $root->save();

            $node21 = new MultiCategory(['title' => 'child 2.1']);
            $node21->prependTo($root)->save();

            $this->assertSame(1, $node21->levelValue());

            $node31 = new MultiCategory(['title' => 'child 3.1']);
            $node31->prependTo($node21)->save();
            $this->assertSame(2, $node31->levelValue());

            $_root = $node31->getRoot();
            $this->assertTrue($_root->isRoot());

            $root->refresh();
            $this->assertEquals($root->id, $_root->id);

            $parents = $node31->parents();
            $this->assertCount(2, $parents);
            $this->assertSame(2, $node31->levelValue());
        }
    }

    #[Test]
    public function insert_before_node_exception(): void
    {
        $this->makeTree(null, 10);
        $roots = MultiCategory::all();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $this->expectException(UniqueRootException::class);
        $node21->insertBefore($roots[0])->save();
    }

    #[Test]
    public function insert_before_node(): void
    {
        $this->makeTree(null, 10);
        $roots = MultiCategory::all();

        [$root, $root2] = $roots;

        $this->assertSame(0, $root->levelValue());
        $this->assertSame(0, $root2->levelValue());

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();
        $this->assertSame(1, $node21->levelValue());

        $node22 = new MultiCategory(['title' => 'child 2.2']);
        $node22->insertBefore($node21)->save();
        $this->assertSame(1, $node22->levelValue());

        $this->assertCount(2, $root->children);

        $node21->refresh();
        $node22->refresh();
        $root->refresh();

        $this->assertEquals($root->id, $node21->parent->id);
        $this->assertEquals($root->id, $node22->parent->id);

        $this->assertEquals(1, $node21->levelValue());
        $this->assertEquals(1, $node22->levelValue());

        $this->assertEquals($node22->id, $node21->siblings()->get()->first()->id);
        $this->assertEquals($node21->id, $node22->siblings()->get()->first()->id);

        $this->assertEquals($node22->id, $node21->prev()->first()->id);
        $this->assertEquals($node21->id, $node22->next()->first()->id);
    }

    #[Test]
    public function insert_after_node(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node22 = new MultiCategory(['title' => 'child 2.2']);
        $node22->appendTo($root)->save();
        $this->assertSame(1, $node22->levelValue());

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->insertAfter($node22)->save();
        $this->assertSame(1, $node21->levelValue());

        $this->assertCount(2, $root->children);

        $node21->refresh();
        $node22->refresh();
        $root->refresh();

        $this->assertEquals($root->id, $node21->parent->id);
        $this->assertEquals($root->id, $node22->parent->id);

        $this->assertEquals(1, $node21->levelValue());
        $this->assertEquals(1, $node22->levelValue());

        $this->assertEquals($node22->id, $node21->siblings()->get()->first()->id);
        $this->assertEquals($node21->id, $node22->siblings()->get()->first()->id);

        $this->assertEquals($node22->id, $node21->prev()->first()->id);
        $this->assertEquals($node21->id, $node22->next()->first()->id);
    }

    #[Test]
    public function insert_after_root(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();
        $node21->insertAfter($root)->save();
        $this->assertTrue($node21->isRoot());
    }

    #[Test]
    public function insert_before_root(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();
        $node21->insertBefore($root)->save();
        $this->assertTrue($node21->isRoot());
    }

    #[Test]
    public function append_to_same_exception(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();
        $this->expectException(Exception::class);
        $node21->appendTo($node21)->save();
    }

    #[Test]
    public function append_to_non_exist_parent_exception(): void
    {
        $root = new MultiCategory(['title' => 'root']);
        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $this->expectException(Exception::class);
        $node21->appendTo($root)->save();
    }

    #[Test]
    public function prepend_to_same_exception(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();
        $this->expectException(Exception::class);
        $node21->prependTo($node21)->save();
    }

    #[Test]
    public function move_to_self_children_exception(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->appendTo($root)->save();

        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node31->appendTo($node21)->save();

        $node21->refresh();
        $this->assertTrue($node31->isChildOf($node21));

        $this->expectException(Exception::class);
        $node21->appendTo($node31)->save();
    }

    #[Test]
    public function insert_after_node_exception(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $this->expectException(UniqueRootException::class);
        $node21->insertAfter($root)->save();
    }

    #[Test]
    public function delete_root_node(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $root->delete();

        $this->assertEquals(9, MultiCategory::count());
    }

    #[Test]
    public function delete_root_with_child_node(): void
    {
        $this->makeTree(null, 10, 1);
        $root = MultiCategory::first();

        $this->expectException(DeletedNodeHasChildrenException::class);
        $root->delete();
    }

    #[Test]
    public function delete_node(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();
        $this->assertSame(1, $node21->levelValue());

        $root->refresh();
        $this->assertTrue($node21->isLeaf());
        $this->assertTrue($node21->isChildOf($root));

        $this->assertTrue($node21->delete());

        $root->refresh();
        $this->assertTrue($root->isLeaf());
        $this->assertEmpty($root->children()->count());

        $node41 = new MultiCategory(['title' => 'child 4.1']);
        $node41->delete();
    }

    #[Test]
    public function delete_children_node(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();
        $this->assertSame(1, $node21->levelValue());

        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node31->prependTo($node21)->save();
        $this->assertSame(2, $node31->levelValue());

        $root->refresh();
        $node21->refresh();

        $this->assertFalse($node21->isLeaf());
        $this->assertTrue($node31->isLeaf());
        $this->assertTrue($node31->isChildOf($root));
        $this->assertTrue($node21->delete());

        $node31->refresh();

        $this->assertSame(1, $node31->levelValue());
        $this->assertTrue($node31->isLeaf());
    }

    #[Test]
    public function delete_with_children_node(): void
    {
        $this->makeTree(null, 10);
        /** @var MultiCategory $root */
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node21->prependTo($root)->save();
        $this->assertSame(1, $node21->levelValue());

        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node31->prependTo($node21)->save();
        $this->assertSame(2, $node31->levelValue());

        $node41 = new MultiCategory(['title' => 'child 4.1']);
        $node41->prependTo($node31)->save();
        $this->assertSame(3, $node41->levelValue());

        $root->refresh();
        $node21->refresh();
        $node31->refresh();

        $this->assertFalse($node21->isLeaf());
        $this->assertFalse($node31->isLeaf());
        $this->assertTrue($node41->isLeaf());
        $this->assertTrue($node41->isChildOf($root));
        $this->assertTrue($node41->isChildOf($node21));
        $this->assertTrue($node41->isChildOf($node31));

        $delNode = $node21->deleteWithChildren();

        $this->assertEquals(3, $delNode);
        $root->refresh();
        $this->assertTrue($root->isLeaf());
        $this->assertEmpty($root->children()->count());

        $this->assertEquals(1, $root->leftValue());
        $this->assertEquals(2, $root->rightValue());

        $node31 = new MultiCategory(['title' => 'child 3.1 new']);
        $node31->appendTo($root)->save();
        $this->assertSame(1, $node31->levelValue());

        $node41 = new MultiCategory(['title' => 'child 4.1 new ']);
        $node41->appendTo($node31)->save();
        $this->assertSame(2, $node41->levelValue());

        $node51 = new MultiCategory(['title' => 'child 5.1 new ']);
        $node51->prependTo($node41)->save();
        $this->assertSame(3, $node51->levelValue());

        $root->refresh();
        $node51->refresh();
        $node31->refresh();

        $this->assertTrue($node51->isLeaf());
        $this->assertTrue($node51->isChildOf($root));
        $this->assertTrue($node51->isChildOf($node31));

        $this->assertEquals(1, $root->leftValue());
        $this->assertEquals(8, $root->rightValue());

        $node21->prependTo($root)->save();
        $this->assertSame(1, $node21->levelValue());
    }

    #[Test]
    public function move(): void
    {
        $this->makeTree(null, 1);
        $root1 = MultiCategory::find(1);

        $node21 = new MultiCategory(['title' => 'child 1.1']);
        $node21->prependTo($root1)->save();
        $this->assertSame(1, $node21->levelValue());

        $node31 = new MultiCategory(['title' => 'child 1.1.1']);
        $node31->prependTo($node21)->save();
        $this->assertSame(2, $node31->levelValue());

        $node31->appendTo($root1)->save();
        $node31->refresh();
        $this->assertSame(1, $node31->levelValue());

        $this->assertEquals($root1->id, $node31->parent->id);
        $this->assertCount(2, $root1->children);

        $node31->appendTo($node21)->save();
        $node31->refresh();
        $this->assertSame(2, $node31->levelValue());

        $node21->refresh();
        $root1->refresh();

        $this->assertEquals($node21->id, $node31->parent->id);
        $this->assertCount(1, $root1->children);
        $this->assertCount(1, $node21->children);
    }

    #[Test]
    public function move_between_roots(): void
    {
        $this->makeTree(null, 2);
        $root1 = MultiCategory::find(1);
        $root2 = MultiCategory::find(2);

        $node21 = new MultiCategory(['title' => 'child 1.1']);
        $node21->prependTo($root1)->save();
        $node31 = new MultiCategory(['title' => 'child 1.1.1']);
        $node31->prependTo($node21)->save();

        $node21->appendTo($root2)->save();
        $node21->refresh();
        $this->assertSame(1, $node21->levelValue());
        $this->assertSame(2, $node21->treeValue());

        $root1->refresh();
        $root2->refresh();

        $this->assertCount(0, $root1->children);
        $this->assertCount(1, $root2->children);
    }

    #[Test]
    public function uses_soft_delete(): void
    {
        $model = new MultiCategory(['id' => 1, 'title' => 'root node']);
        $this->assertFalse($model::isSoftDelete());
    }

    #[Test]
    public function get_bounds(): void
    {
        $model = (new MultiCategory(['id' => 1, 'title' => 'root node']))->makeRoot();
        $model->save();

        $this->assertIsArray($model->getBounds());
        $this->assertCount(5, $model->getBounds());
        $this->assertEquals(1, $model->getBounds()[0]);
        $this->assertEquals(2, $model->getBounds()[1]);
        $this->assertEquals(0, $model->getBounds()[2]);
        $this->assertEquals(null, $model->getBounds()[3]);
        $this->assertEquals(1, $model->getBounds()[4]);
    }

    #[Test]
    public function get_node_bounds(): void
    {
        $model = (new MultiCategory(['id' => 1, 'title' => 'root node']))->makeRoot();
        $model->save();

        $data_1 = $model->getNodeBounds($model);
        $data_2 = $model->getNodeBounds($model->getKey());
        $this->assertIsArray($data_1);
        $this->assertIsArray($data_2);
        $this->assertCount(5, $data_1);
        $this->assertEquals($data_2, $data_1);
    }

    #[Test]
    public function descendants(): void
    {
        $this->makeTree(null, 10);
        /** @var MultiCategory $root */
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node41 = new MultiCategory(['title' => 'child 4.1']);
        $node32 = new MultiCategory(['title' => 'child 3.2']);
        $node321 = new MultiCategory(['title' => 'child 3.2.1']);

        $node21->appendTo($root)->save();
        $node31->appendTo($root)->save();
        $node41->appendTo($root)->save();
        $node32->appendTo($node31)->save();
        $node321->appendTo($node32)->save();

        $root->refresh();

        $this->assertEquals(5, $root->descendants()->count());
    }

    #[Test]
    public function ancestors(): void
    {
        $this->makeTree(null, 3);
        $roots = MultiCategory::all();

        $root3 = $roots[2];
        $root1 = $roots[0];

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node41 = new MultiCategory(['title' => 'child 4.1']);
        $node32 = new MultiCategory(['title' => 'child 3.2']);
        $node321 = new MultiCategory(['title' => 'child 3.2.1']);

        $node21->appendTo($root3)->save();
        $node31->appendTo($root3)->save();
        $node41->appendTo($root3)->save();
        $node32->appendTo($node31)->save();
        $node321->appendTo($node32)->save();

        $node32->refresh();
        $node31->refresh();
        $node41->refresh();

        (new MultiCategory(['title' => 'child #1 - 2.1']))->appendTo($root1)->save();
        (new MultiCategory(['title' => 'child #1 - 3.1']))->appendTo($root1)->save();
        (new MultiCategory(['title' => 'child #1 - 4.1']))->appendTo($root1)->save();

        // @todo: need benchmarks
        $this->assertEquals(3, $node321->ancestors()->count());
        $this->assertEquals(3, $node321->parents()->count());

        $this->assertEquals(2, $node32->ancestors()->count());
        $this->assertEquals(2, $node32->parents()->count());

        $this->assertEquals(1, $node31->ancestors()->count());
        $this->assertEquals(1, $node31->parents()->count());

        $this->assertEquals(1, $node41->ancestors()->count());
        $this->assertEquals(1, $node41->parents()->count());

        $this->assertEquals(1, $node21->ancestors()->count());
        $this->assertEquals(1, $node21->parents()->count());
    }

    #[Test]
    public function base_save_exception(): void
    {
        $model = new MultiCategory(['id' => 2, 'title' => 'node']);
        $model->save();
        $this->assertTrue($model->exists);
        $this->assertEquals(1, $model->getKey());
    }

    #[Test]
    public function up(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node41 = new MultiCategory(['title' => 'child 4.1']);

        $node21->appendTo($root)->save();
        $node31->appendTo($root)->save();
        $node41->appendTo($root)->save();

        $children = $root->children()->defaultOrder()->get()->map(
            static function ($item) {
                return $item->title;
            }
        );

        $this->assertCount(3, $children);
        $this->assertEquals(['child 2.1', 'child 3.1', 'child 4.1'], $children->toArray());

        $this->assertTrue($node31->up());
        $this->assertFalse($node31->isForceSaving());

        $children = $root->children()->defaultOrder()->get()->map(
            static function ($item) {
                return $item->title;
            }
        );

        $this->assertEquals(['child 3.1', 'child 2.1', 'child 4.1'], $children->toArray());
        $node31->refresh();

        $this->assertFalse($node31->up());
        $this->assertFalse($node31->isForceSaving());

        $children = $root->children()->defaultOrder()->get()->map(
            static function ($item) {
                return $item->title;
            }
        );

        $this->assertEquals(['child 3.1', 'child 2.1', 'child 4.1'], $children->toArray());
    }

    #[Test]
    public function down(): void
    {
        $this->makeTree(null, 10);
        $root = MultiCategory::first();

        $node21 = new MultiCategory(['title' => 'child 2.1']);
        $node31 = new MultiCategory(['title' => 'child 3.1']);
        $node41 = new MultiCategory(['title' => 'child 4.1']);

        $node21->appendTo($root)->save();
        $node31->appendTo($root)->save();
        $node41->appendTo($root)->save();

        $children = $root->children()->defaultOrder()->get()->map(
            function ($item) {
                return $item->title;
            }
        );

        $this->assertCount(3, $children);
        $this->assertEquals(['child 2.1', 'child 3.1', 'child 4.1'], $children->toArray());

        $this->assertTrue($node31->down());
        $this->assertFalse($node31->isForceSaving());

        $children = $root->children()->defaultOrder()->get()->map(
            function ($item) {
                return $item->title;
            }
        );

        $this->assertEquals(['child 2.1', 'child 4.1', 'child 3.1'], $children->toArray());

        $node31->refresh();
        $this->assertFalse($node31->down());
        $this->assertFalse($node31->isForceSaving());

        $children = $root->children()->defaultOrder()->get()->map(
            function ($item) {
                return $item->title;
            }
        );

        $this->assertEquals(['child 2.1', 'child 4.1', 'child 3.1'], $children->toArray());
    }

    #[Test]
    public function get_node_data(): void
    {
        $this->makeTree(null, 10);
        $roots = MultiCategory::all();

        foreach ($roots as $root) {
            $data = MultiCategory::getNodeData($root->getKey());
            $this->assertEquals(
                [
                    'lft' => 1,
                    'rgt' => 2,
                    'lvl' => 0,
                    'parent_id' => null,
                    'tree_id' => $root->getKey(),
                ],
                $data
            );
        }
    }

    #[Test]
    public function get_by_levels(): void
    {
        $treeChildrenMap = [
            4,
            2,
            3,
            1,
        ];
        $this->makeTree(null, ...$treeChildrenMap);

        for ($i = 0, $iMax = count($treeChildrenMap); $i < $iMax; $i++) {
            $count = MultiCategory::toLevel($i)->count();
            $this->assertEquals($this->sum($treeChildrenMap, $i), $count);
        }
    }
}
