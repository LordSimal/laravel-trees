<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Generators\GeneratorTreeId;
use LordSimal\LaravelTrees\Tests\Models\MultiCategory;
use PHPUnit\Framework\Attributes\Test;

class GeneratorTreeIdIntTest extends AbstractUnitTestCase
{
    protected static string $modelClass = MultiCategory::class;

    #[Test]
    public function generateMaxId(): void
    {
        $model = (new MultiCategory(['title' => 'root']))->makeRoot();
        $model->save();
        $generator = new GeneratorTreeId($model->getTreeConfig()->parent);
        $this->assertEquals(2, $generator->generateId($model));
    }
}
