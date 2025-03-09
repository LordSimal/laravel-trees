<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Builder;

use LordSimal\LaravelTrees\Tests\Models\Category;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class HealthyTraitTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function count_errors(): void
    {
        static::makeTree(null, 1, 3, 2, 1, 1);

        $data = Category::countErrors();
        static::assertEquals(
            [
                'oddness' => 0,
                'duplicates' => 0,
                'wrong_parent' => 0,
                'missing_parent' => 0,
            ],
            $data
        );

        $oddness = Category::countErrors('oddness');
        static::assertEmpty($oddness);
    }

    #[Test]
    public function is_broken(): void
    {
        static::makeTree(null, 1, 3, 2, 1, 1);

        static::assertFalse(Category::isBroken());
    }
}
