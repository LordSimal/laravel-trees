<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Relations;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class DescendantsMultiRelationsTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function descendants(): void
    {
        $this->makeTree(null, 2, 2);
        $category = MultiCategory::find(1);
        $descendants = $category->descendants;

        $this->assertCount(2, $descendants);
        $this->assertEquals([2, 3], $descendants->pluck('id')->toArray());
    }
}
