<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\CustomModel;
use PHPUnit\Framework\Attributes\Test;

class CollectionCustomModelTest extends AbstractUnitTestCase
{
    protected static string $modelClass = CustomModel::class;

    #[Test]
    public function link_nodes(): void
    {
        $childrenTree = [
            2,
            3,
            2,
            3,
        ];
        $this->makeTree(null, ...$childrenTree);

        $this->assertEquals(56, $this->sum($childrenTree));
        $this->assertCount($this->sum($childrenTree), CustomModel::all());

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());

        /** @var CustomModel $root */
        $roots = CustomModel::root()->get();

        $this->assertCount(2, $roots);
        $root1 = $roots->first();
        $root2 = $roots->last();

        $collection1 = CustomModel::byTree($root1->treeValue())->get();
        $collection2 = CustomModel::byTree($root2->treeValue())->get();

        $this->assertCount(28, $collection1);
        $this->assertCount(28, $collection2);

        $this->assertCount($preQueryCount += 3, $root1->getConnection()->getQueryLog());

        foreach ($roots as $root) {
            $this->assertEquals(CustomModel::TREE_ID, $root->treeAttribute()->columnName());
            $this->assertEquals(CustomModel::PARENT_ID, $root->parentAttribute()->columnName());
            $this->assertEquals('custom_left', $root->leftAttribute()->columnName());
            $this->assertEquals('custom_right', $root->rightAttribute()->columnName());
            $this->assertEquals('custom_level', $root->levelAttribute()->columnName());

            $collection = CustomModel::byTree($root->treeValue())->get();
            $preQueryCount++;
            $this->assertCount($preQueryCount, $root->getConnection()->getQueryLog());
            $collection->linkNodes();

            $collectionRoot = $collection->where($root->parentAttribute()->columnName(), '=', null)->first();
            $collectionRoot2 = $collection->getRoots()->first();
            $this->assertEquals($collectionRoot, $collectionRoot2);

            $this->assertCount($preQueryCount, $root->getConnection()->getQueryLog());

            $this->assertCount(3, $collectionRoot->children);
            $this->assertNull($collectionRoot->parent);

            $this->assertCount($preQueryCount, $root->getConnection()->getQueryLog());

            foreach ($collectionRoot->children as $children1) {
                $this->assertCount(2, $children1->children);
                $this->assertEquals($collectionRoot->id, $children1->parent->id);

                foreach ($children1->children as $children2) {
                    $this->assertCount(3, $children2->children);
                    $this->assertEquals($children1->id, $children2->parent->id);
                }
            }
        }

        $this->assertCount($preQueryCount, $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function to_link_nodes(): void
    {
        $this->makeTree(null, 2, 3, 2, 3);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 2;

        $root = CustomModel::root()->first();
        $collection = CustomModel::byTree($root->treeValue())->get();

        /** @var CustomModel $root */
        $root = $collection->getRoots()->first();

        $this->assertCount($expectedQueryCount, $root->getConnection()->getQueryLog());

        $this->assertCount(3, $root->children);
        $this->assertNull($root->parent);
        $this->assertCount($expectedQueryCount + 1, $root->getConnection()->getQueryLog());

        foreach ($root->children as $children1) {
            $this->assertCount(2, $children1->children);
            $this->assertEquals($root->id, $children1->parent->id);
        }

        $this->assertCount($expectedQueryCount + 7, $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function to_tree_with_root_node(): void
    {
        $childrenNodesMap = [
            2,
            3,
            2,
            3,
        ];
        $this->makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 2;

        $root = CustomModel::root()->first();
        $list = CustomModel::byTree($root->treeValue())->get();

        $this->assertCount($this->sum($childrenNodesMap) / 2, $list);

        /** @var CustomModel $root */
        $root = $list->getRoots()->first();
        $tree = $list->toTree($root);

        $this->assertCount(3, $tree);
        $this->assertNull($root->parent);

        foreach ($root->children as $children1) {
            $this->assertCount(2, $children1->children);
            $this->assertEquals($root->id, $children1->parent->id);
        }

        $this->assertCount($expectedQueryCount + $root->children->count(), $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function to_tree_with_out_root_node(): void
    {
        $childrenNodesMap = [
            2,
            3,
        ];
        $this->makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = CustomModel::all();
        $this->assertCount($this->sum($childrenNodesMap), $list);

        $tree = $list->toTree();
        $this->assertCount(2, $tree);

        foreach ($tree as $page) {
            $this->assertCount(3, $page['children']);
        }

        $this->assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }

    #[Test]
    public function to_tree_custom_levels(): void
    {
        $childrenNodesMap = [
            2,
            3,
            1,
            2,
        ];
        $this->makeTree(null, ...$childrenNodesMap);

        foreach ($childrenNodesMap as $level => $childrenCount) {
            $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
            $expectedQueryCount = $preQueryCount + 1;

            $list = CustomModel::toLevel($level)->get();
            $this->assertCount($this->sum($childrenNodesMap, $level), $list);

            $this->assertEmpty(
                $list->filter(
                    function ($item) use ($level) {
                        return $item->levelValue() > $level;
                    }
                )
            );

            $this->assertCount(
                $this->sum($childrenNodesMap, $level),
                $list->filter(
                    function ($item) use ($level) {
                        return $item->levelValue() <= $level;
                    }
                )
            );

            $tree = $list->toTree();

            $this->assertCount(2, $tree);
            $this->assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
        }
    }

    #[Test]
    public function to_tree_array_multi_roots(): void
    {
        $childrenNodesMap = [
            5,
            3,
            2,
        ];
        $this->makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = CustomModel::all();

        $this->assertCount($this->sum($childrenNodesMap), $list);

        $tree = $list->toTree()->toArray();

        $this->assertCount(5, $tree);

        foreach ($tree as $pages) {
            $this->assertCount(3, $pages['children']);

            foreach ($pages['children'] as $page) {
                $this->assertCount(2, $page['children']);
            }
        }

        $this->assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }

    #[Test]
    public function get_roots(): void
    {
        $this->makeTree(null, 6, 1, 2, 1);

        $list = CustomModel::all();
        $expectedQueryCount = count((new CustomModel())->getConnection()->getQueryLog());

        $this->assertCount(36, $list);

        $roots = $list->getRoots();

        $this->assertCount(6, $roots);

        $this->assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }
}
