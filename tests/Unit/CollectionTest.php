<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class CollectionTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

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

        $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = MultiCategory::byTree(1)->get();

        static::assertCount(static::sum($childrenNodesMap) / 2, $list);

        /** @var MultiCategory $root */
        $root = $list->where('parent_id', '=', null)->first();
        $tree = $list->toTree($root);

        static::assertCount(3, $tree);
        static::assertNull($root->parent);

        foreach ($root->children as $children1) {
            static::assertCount(2, $children1->children);
            static::assertEquals($root->id, $children1->parent->id);
        }

        static::assertCount($expectedQueryCount + count($root->children), $root->getConnection()->getQueryLog());
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
            $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
            $expectedQueryCount = $preQueryCount + 1;

            $list = MultiCategory::toLevel($level)->get();
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

        $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = MultiCategory::all();
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
}
