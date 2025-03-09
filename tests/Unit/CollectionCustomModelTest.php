<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\CustomModel;
use PHPUnit\Framework\Attributes\Test;

class CollectionCustomModelTest extends AbstractUnitTestCase
{
    protected static string $modelClass = CustomModel::class;

    #[Test]
    public function linkNodes(): void
    {
        $childrenTree = [
            2,
            3,
            2,
            3,
        ];
        static::makeTree(null, ...$childrenTree);

        static::assertEquals(56, static::sum($childrenTree));
        static::assertCount(static::sum($childrenTree), CustomModel::all());

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());

        /** @var CustomModel $root */
        $roots = CustomModel::root()->get();

        static::assertCount(2, $roots);
        $root1 = $roots->first();
        $root2 = $roots->last();

        $collection1 = CustomModel::byTree($root1->treeValue())->get();
        $collection2 = CustomModel::byTree($root2->treeValue())->get();

        static::assertCount(28, $collection1);
        static::assertCount(28, $collection2);

        static::assertCount($preQueryCount += 3, $root1->getConnection()->getQueryLog());

        foreach ($roots as $root) {
            static::assertEquals(CustomModel::TREE_ID, $root->treeAttribute()->columnName());
            static::assertEquals(CustomModel::PARENT_ID, $root->parentAttribute()->columnName());
            static::assertEquals('custom_left', $root->leftAttribute()->columnName());
            static::assertEquals('custom_right', $root->rightAttribute()->columnName());
            static::assertEquals('custom_level', $root->levelAttribute()->columnName());

            $collection = CustomModel::byTree($root->treeValue())->get();
            $preQueryCount++;
            static::assertCount($preQueryCount, $root->getConnection()->getQueryLog());
            $collection->linkNodes();

            $collectionRoot = $collection->where($root->parentAttribute()->columnName(), '=', null)->first();
            $collectionRoot2 = $collection->getRoots()->first();
            static::assertEquals($collectionRoot, $collectionRoot2);

            static::assertCount($preQueryCount, $root->getConnection()->getQueryLog());

            static::assertCount(3, $collectionRoot->children);
            static::assertNull($collectionRoot->parent);

            static::assertCount($preQueryCount, $root->getConnection()->getQueryLog());

            foreach ($collectionRoot->children as $children1) {
                static::assertCount(2, $children1->children);
                static::assertEquals($collectionRoot->id, $children1->parent->id);

                foreach ($children1->children as $children2) {
                    static::assertCount(3, $children2->children);
                    static::assertEquals($children1->id, $children2->parent->id);
                }
            }
        }

        static::assertCount($preQueryCount, $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function toLinkNodes(): void
    {
        static::makeTree(null, 2, 3, 2, 3);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 2;

        $root = CustomModel::root()->first();
        $collection = CustomModel::byTree($root->treeValue())->get();

        /** @var CustomModel $root */
        $root = $collection->getRoots()->first();

        static::assertCount($expectedQueryCount, $root->getConnection()->getQueryLog());

        static::assertCount(3, $root->children);
        static::assertNull($root->parent);
        static::assertCount($expectedQueryCount + 1, $root->getConnection()->getQueryLog());

        foreach ($root->children as $children1) {
            static::assertCount(2, $children1->children);
            static::assertEquals($root->id, $children1->parent->id);
        }

        static::assertCount($expectedQueryCount + 7, $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function toTreeWithRootNode(): void
    {
        $childrenNodesMap = [
            2,
            3,
            2,
            3,
        ];
        static::makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 2;

        $root = CustomModel::root()->first();
        $list = CustomModel::byTree($root->treeValue())->get();

        static::assertCount(static::sum($childrenNodesMap) / 2, $list);

        /** @var CustomModel $root */
        $root = $list->getRoots()->first();
        $tree = $list->toTree($root);

        static::assertCount(3, $tree);
        static::assertNull($root->parent);

        foreach ($root->children as $children1) {
            static::assertCount(2, $children1->children);
            static::assertEquals($root->id, $children1->parent->id);
        }

        static::assertCount($expectedQueryCount + $root->children->count(), $root->getConnection()->getQueryLog());
    }

    #[Test]
    public function toTreeWithOutRootNode(): void
    {
        $childrenNodesMap = [
            2,
            3,
        ];
        static::makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = CustomModel::all();
        static::assertCount(static::sum($childrenNodesMap), $list);

        $tree = $list->toTree();
        static::assertCount(2, $tree);

        foreach ($tree as $page) {
            static::assertCount(3, $page['children']);
        }

        static::assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }

    #[Test]
    public function toTreeCustomLevels(): void
    {
        $childrenNodesMap = [
            2,
            3,
            1,
            2,
        ];
        static::makeTree(null, ...$childrenNodesMap);

        foreach ($childrenNodesMap as $level => $childrenCount) {
            $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
            $expectedQueryCount = $preQueryCount + 1;

            $list = CustomModel::toLevel($level)->get();
            static::assertCount(static::sum($childrenNodesMap, $level), $list);

            static::assertEmpty(
                $list->filter(
                    function ($item) use ($level) {
                        return $item->levelValue() > $level;
                    }
                )
            );

            static::assertCount(
                static::sum($childrenNodesMap, $level),
                $list->filter(
                    function ($item) use ($level) {
                        return $item->levelValue() <= $level;
                    }
                )
            );

            $tree = $list->toTree();

            static::assertCount(2, $tree);
            static::assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
        }
    }

    #[Test]
    public function toTreeArrayMultiRoots(): void
    {
        $childrenNodesMap = [
            5,
            3,
            2,
        ];
        static::makeTree(null, ...$childrenNodesMap);

        $preQueryCount = count((new CustomModel())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = CustomModel::all();

        static::assertCount(static::sum($childrenNodesMap), $list);

        $tree = $list->toTree()->toArray();

        static::assertCount(5, $tree);

        foreach ($tree as $pages) {
            static::assertCount(3, $pages['children']);

            foreach ($pages['children'] as $page) {
                static::assertCount(2, $page['children']);
            }
        }

        static::assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }

    #[Test]
    public function getRoots(): void
    {
        static::makeTree(null, 6, 1, 2, 1);

        $list = CustomModel::all();
        $expectedQueryCount = count((new CustomModel())->getConnection()->getQueryLog());

        static::assertCount(36, $list);

        $roots = $list->getRoots();

        static::assertCount(6, $roots);

        static::assertCount($expectedQueryCount, $list->first()->getConnection()->getQueryLog());
    }
}
