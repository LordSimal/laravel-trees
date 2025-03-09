<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Builder;

use LordSimal\LaravelTrees\Tests\Models\Category;
use LordSimal\LaravelTrees\Tests\Unit\AbstractUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class FixingTraitTest extends AbstractUnitTestCase
{
    protected static string $modelClass = Category::class;

    #[Test]
    public function fixWithoutErrors(): void
    {
        $this->makeTree(null, 1, 2, 4);

        static::assertEquals(0, Category::fixTree());
    }

    #[Test]
    public function fixWithOddnessError(): void
    {
        $this->makeTree(null, 1, 2, 4);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        static::assertEquals(1, $oddness);
        Category::fixTree();
        static::assertEquals(0, Category::query()->countErrors('oddness'));
    }

    #[Test]
    public function fixWithOddnessErrorAndParent(): void
    {
        $this->makeTree(null, 1, 2, 3);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        static::assertEquals(1, $oddness);
        Category::fixTree($brokenModel->parent);
        static::assertEquals(0, Category::query()->countErrors('oddness'));
    }

    #[Test]
    public function fixWithAdjustedRightValueInSubtree(): void
    {
        $this->makeTree(null, 1, 2, 3);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(3);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute((string) $brokenModel->rightAttribute(), $brokenModel->rightValue() + 2);
        $brokenModel->saveQuietly();

        $oddness = Category::query()->countErrors();

        static::assertEquals([
            'oddness' => 0,
            'duplicates' => 2,
            'wrong_parent' => 1,
            'missing_parent' => 0,
        ], $oddness);
        Category::fixTree($brokenModel->parent);
        static::assertEquals([
            'oddness' => 0,
            'duplicates' => 0,
            'wrong_parent' => 0,
            'missing_parent' => 0,
        ], Category::query()->countErrors());
    }

    #[Test]
    public function fixWithDuplicateError(): void
    {
        $this->makeTree(null, 1, 2);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(2);

        // Manually change the left and right values to create a duplicate
        $brokenModel->setAttribute($brokenModel->leftAttribute()->name()->value, 4);
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, 5);
        $brokenModel->save();

        $duplicates = Category::query()->countErrors('duplicates');

        static::assertEquals(2, $duplicates);
        Category::fixTree();
        static::assertEquals(0, Category::query()->countErrors('duplicates'));
    }

    #[Test]
    public function fixWithWrongParentError(): void
    {
        $this->makeTree(null, 1, 2, 1);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(5);

        // Manually change the parent to create a wrong parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, 2);
        $brokenModel->save();

        $wrongParents = Category::query()->countErrors('wrong_parent');

        static::assertEquals(3, $wrongParents);
        Category::fixTree();
        static::assertEquals(0, Category::query()->countErrors('wrong_parent'));
    }

    #[Test]
    public function fixWithMissingParentError(): void
    {
        $this->makeTree(null, 1, 2);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(3);

        // Manually change the parent to create a missing parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, -130);
        $brokenModel->save();

        $missingParents = Category::query()->countErrors('missing_parent');

        static::assertEquals(1, $missingParents);
        Category::fixTree();
        static::assertEquals(0, Category::query()->countErrors('missing_parent'));
    }

    #[Test]
    public function fixWithMultiCallWorks(): void
    {
        $this->makeTree(null, 1, 2, 4);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        static::assertEquals(1, $oddness);
        Category::fixMultiTree();
        static::assertEquals(0, Category::query()->countErrors('oddness'));
    }
}
