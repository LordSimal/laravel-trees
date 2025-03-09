<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Relations;

use LordSimal\LaravelTrees\Tests\Models\Category;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class DescendantsSingleRelationsTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function descendants(): void
    {
        $this->makeTree(null, 1, 2);
        $category = Category::find(1);
        $descendants = $category->descendants;

        $this->assertCount(2, $descendants);
        $this->assertEquals([2, 3], $descendants->pluck('id')->toArray());
    }
}
