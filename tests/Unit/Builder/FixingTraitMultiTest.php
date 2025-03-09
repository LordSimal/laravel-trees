<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Builder;

use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class FixingTraitMultiTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function fixWithoutErrors(): void
    {
        $this->makeTree(null, 1, 2, 4);
        $this->makeTree(null, 1, 2, 4);

        static::assertEquals(
            [
                1 => 0,
                2 => 0,
            ],
            MultiCategory::fixMultiTree()
        );
    }

    #[Test]
    public function fixWithOddnessError(): void
    {
        $this->makeTree(null, 1, 2, 4);
        $this->makeTree(null, 1, 2, 4);

        /** @var MultiCategory $brokenModel */
        $brokenModel = MultiCategory::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = MultiCategory::query()->countErrors('oddness');

        static::assertEquals(1, $oddness);
        MultiCategory::fixMultiTree();
        static::assertEquals(0, MultiCategory::query()->countErrors('oddness'));
    }

    #[Test]
    public function fixWithDuplicateError(): void
    {
        $this->makeTree(null, 1, 2);
        $this->makeTree(null, 1, 2);

        /** @var MultiCategory $brokenModel */
        $brokenModel = MultiCategory::query()->find(2);

        // Manually change the left and right values to create a duplicate
        $brokenModel->setAttribute($brokenModel->leftAttribute()->name()->value, 4);
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, 5);
        $brokenModel->save();

        $duplicates = MultiCategory::query()->countErrors('duplicates');

        static::assertEquals(2, $duplicates);
        MultiCategory::fixMultiTree();
        static::assertEquals(0, MultiCategory::query()->countErrors('duplicates'));
    }

    #[Test]
    public function fixWithWrongParentError(): void
    {
        $this->makeTree(null, 1, 2, 1);
        $this->makeTree(null, 1, 2, 1);

        /** @var MultiCategory $brokenModel */
        $brokenModel = MultiCategory::query()->find(5);

        // Manually change the parent to create a wrong parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, 2);
        $brokenModel->save();

        $wrongParents = MultiCategory::query()->countErrors('wrong_parent');

        static::assertEquals(3, $wrongParents);
        MultiCategory::fixTree();
        static::assertEquals(0, MultiCategory::query()->countErrors('wrong_parent'));
    }

    #[Test]
    public function fixWithMissingParentError(): void
    {
        $this->makeTree(null, 1, 2);
        $this->makeTree(null, 1, 2);

        /** @var MultiCategory $brokenModel */
        $brokenModel = MultiCategory::query()->find(3);

        // Manually change the parent to create a missing parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, -130);
        $brokenModel->save();

        $missingParents = MultiCategory::query()->countErrors('missing_parent');

        static::assertEquals(1, $missingParents);
        MultiCategory::fixTree();
        static::assertEquals(0, MultiCategory::query()->countErrors('missing_parent'));
    }
}
