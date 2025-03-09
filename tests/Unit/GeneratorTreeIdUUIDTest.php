<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use LordSimal\LaravelTrees\Generators\GeneratorTreeId;
use LordSimal\LaravelTrees\Tests\Models\CustomModel;
use PHPUnit\Framework\Attributes\Test;

class GeneratorTreeIdUUIDTest extends AbstractUnitTestCase
{
    protected static string $modelClass = CustomModel::class;

    #[Test]
    public function generateMaxId(): void
    {
        $model = (new CustomModel(['title' => 'root']))->makeRoot();
        $model->save();
        $generator = new GeneratorTreeId($model->getTreeConfig()->tree);
        $uuid = $generator->generateId($model);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }
}
