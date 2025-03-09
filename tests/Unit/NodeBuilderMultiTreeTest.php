<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class NodeBuilderMultiTreeTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function by_tree(): void
    {
        static::makeTree(null, 3, 2, 1);

        $roots = MultiCategory::root()->get();

        foreach ($roots as $node) {
            $nodesRootCheck = MultiCategory::root()->byTree($node->treeValue())->get();
            static::assertCount(1, $nodesRootCheck);
            $nodeRootCheck = $nodesRootCheck->first();
            static::assertInstanceOf(MultiCategory::class, $nodeRootCheck);
            static::assertEquals($node->id, $nodeRootCheck->id);

            $nodesCheck = MultiCategory::byTree($node->treeValue())->get();
            static::assertCount(5, $nodesCheck);
            $treeId = $node->treeValue();

            static::assertCount(
                5,
                $nodesCheck->map->tree_id->filter(fn ($item) => $item === $treeId)
            );
        }
    }
}
