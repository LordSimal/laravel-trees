<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Relations;

use LordSimal\LaravelTrees\Tests\Models\Category;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class AncestorsSingleRelationsTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function ancestor(): void
    {
        $this->makeTree(null, 1, 2);
        $category = Category::find(2);
        $ancestors = $category->ancestors;

        $this->assertCount(1, $ancestors);
        $this->assertEquals([1], $ancestors->pluck('id')->toArray());
    }
}
