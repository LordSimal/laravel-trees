<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class CollectionTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

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

        $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = MultiCategory::byTree(1)->get();

        $this->assertCount($this->sum($childrenNodesMap) / 2, $list);

        /** @var MultiCategory $root */
        $root = $list->where('parent_id', '=', null)->first();
        $tree = $list->toTree($root);

        $this->assertCount(3, $tree);
        $this->assertNull($root->parent);

        foreach ($root->children as $children1) {
            $this->assertCount(2, $children1->children);
            $this->assertEquals($root->id, $children1->parent->id);
        }

        $this->assertCount($expectedQueryCount + count($root->children), $root->getConnection()->getQueryLog());
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
            $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
            $expectedQueryCount = $preQueryCount + 1;

            $list = MultiCategory::toLevel($level)->get();
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

        $preQueryCount = count((new MultiCategory())->getConnection()->getQueryLog());
        $expectedQueryCount = $preQueryCount + 1;

        $list = MultiCategory::all();
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
}
