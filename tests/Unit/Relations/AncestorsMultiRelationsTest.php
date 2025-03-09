<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Relations;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class AncestorsMultiRelationsTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function ancestor(): void
    {
        $this->makeTree(null, 2, 2);
        $category = MultiCategory::find(2);
        $ancestors = $category->ancestors;

        $this->assertCount(1, $ancestors);
        $this->assertEquals([1], $ancestors->pluck('id')->toArray());
    }
}
