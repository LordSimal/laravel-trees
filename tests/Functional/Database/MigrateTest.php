<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Database;

use Illuminate\Database\Schema\Blueprint;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Config\FieldType;
use LordSimal\LaravelTrees\Database\Migrate;
use LordSimal\LaravelTrees\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\Test;

class MigrateTest extends AbstractTestCase
{
    private static string $tableName = 'test_config';

    protected function getBlueprint(string $table): Blueprint
    {
        return new Blueprint($this->getConnection(), $table);
    }

    #[Test]
    public function columnsForUnoTree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::default();

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        static::assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            static::assertContains($column->getAttributes()['name'], $expectedColumns);
        }
    }

    #[Test]
    public function columnsForMultiTree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::defaultMulti();

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        static::assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            static::assertContains($column->getAttributes()['name'], $expectedColumns);

            if ($column->getAttributes()['name'] === $builder->tree()->columnName()) {
                static::assertEquals('integer', $column->getAttributes()['type']);
                static::assertFalse($column->getAttributes()['nullable']);
                static::assertTrue($column->getAttributes()['unsigned']);
                static::assertNull($column->getAttributes()['default']);
            }

            if ($column->getAttributes()['name'] === $builder->parent()->columnName()) {
                static::assertEquals('integer', $column->getAttributes()['type']);
                static::assertTrue($column->getAttributes()['nullable']);
                static::assertTrue($column->getAttributes()['unsigned']);
                static::assertNull($column->getAttributes()['default']);
            }
        }
    }

    #[Test]
    public function columnsForUuidMultiTree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::defaultMulti();
        $builder->tree()->setType(FieldType::UUID)->setColumnName('tid');

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        static::assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            static::assertContains($column->getAttributes()['name'], $expectedColumns);

            if ($column->getAttributes()['name'] === $builder->tree()->columnName()) {
                static::assertEquals('tid', $column->getAttributes()['name']);
                static::assertEquals('uuid', $column->getAttributes()['type']);
                static::assertFalse($column->getAttributes()['nullable']);
                static::assertNull($column->getAttributes()['default']);
            }

            if ($column->getAttributes()['name'] === $builder->parent()->columnName()) {
                static::assertEquals('integer', $column->getAttributes()['type']);
                static::assertTrue($column->getAttributes()['nullable']);
                static::assertTrue($column->getAttributes()['unsigned']);
                static::assertNull($column->getAttributes()['default']);
            }
        }
    }

    #[Test]
    public function dropColumns(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::default();

        (new Migrate($builder, $table))->dropColumns();

        $cols = $table->getColumns();
        static::assertCount(0, $cols);
    }
}
