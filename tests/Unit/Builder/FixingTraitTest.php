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
    public function fix_without_errors(): void
    {
        $this->makeTree(null, 1, 2, 4);

        $this->assertEquals(0, Category::fixTree());
    }

    #[Test]
    public function fix_with_oddness_error(): void
    {
        $this->makeTree(null, 1, 2, 4);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        $this->assertEquals(1, $oddness);
        Category::fixTree();
        $this->assertEquals(0, Category::query()->countErrors('oddness'));
    }

    #[Test]
    public function fix_with_oddness_error_and_parent(): void
    {
        $this->makeTree(null, 1, 2, 3);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        $this->assertEquals(1, $oddness);
        Category::fixTree($brokenModel->parent);
        $this->assertEquals(0, Category::query()->countErrors('oddness'));
    }

    #[Test]
    public function fix_with_adjusted_right_value_in_subtree(): void
    {
        $this->makeTree(null, 1, 2, 3);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(3);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute((string) $brokenModel->rightAttribute(), $brokenModel->rightValue() + 2);
        $brokenModel->saveQuietly();

        $oddness = Category::query()->countErrors();

        $this->assertEquals([
            'oddness' => 0,
            'duplicates' => 2,
            'wrong_parent' => 1,
            'missing_parent' => 0,
        ], $oddness);
        Category::fixTree($brokenModel->parent);
        $this->assertEquals([
            'oddness' => 0,
            'duplicates' => 0,
            'wrong_parent' => 0,
            'missing_parent' => 0,
        ], Category::query()->countErrors());
    }

    #[Test]
    public function fix_with_duplicate_error(): void
    {
        $this->makeTree(null, 1, 2);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(2);

        // Manually change the left and right values to create a duplicate
        $brokenModel->setAttribute($brokenModel->leftAttribute()->name()->value, 4);
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, 5);
        $brokenModel->save();

        $duplicates = Category::query()->countErrors('duplicates');

        $this->assertEquals(2, $duplicates);
        Category::fixTree();
        $this->assertEquals(0, Category::query()->countErrors('duplicates'));
    }

    #[Test]
    public function fix_with_wrong_parent_error(): void
    {
        $this->makeTree(null, 1, 2, 1);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(5);

        // Manually change the parent to create a wrong parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, 2);
        $brokenModel->save();

        $wrongParents = Category::query()->countErrors('wrong_parent');

        $this->assertEquals(3, $wrongParents);
        Category::fixTree();
        $this->assertEquals(0, Category::query()->countErrors('wrong_parent'));
    }

    #[Test]
    public function fix_with_missing_parent_error(): void
    {
        $this->makeTree(null, 1, 2);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(3);

        // Manually change the parent to create a missing parent error
        $brokenModel->setAttribute($brokenModel->parentAttribute()->name()->value, -130);
        $brokenModel->save();

        $missingParents = Category::query()->countErrors('missing_parent');

        $this->assertEquals(1, $missingParents);
        Category::fixTree();
        $this->assertEquals(0, Category::query()->countErrors('missing_parent'));
    }

    #[Test]
    public function fix_with_multi_call_works(): void
    {
        $this->makeTree(null, 1, 2, 4);

        /** @var Category $brokenModel */
        $brokenModel = Category::query()->find(4);

        // Set a random left value to create an oddness error
        $brokenModel->setAttribute($brokenModel->rightAttribute()->name()->value, -130);
        $brokenModel->save();

        $oddness = Category::query()->countErrors('oddness');

        $this->assertEquals(1, $oddness);
        Category::fixMultiTree();
        $this->assertEquals(0, Category::query()->countErrors('oddness'));
    }
}
