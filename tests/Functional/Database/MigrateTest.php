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
    public function columns_for_uno_tree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::default();

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        $this->assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            $this->assertContains($column->getAttributes()['name'], $expectedColumns);
        }
    }

    #[Test]
    public function columns_for_multi_tree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::defaultMulti();

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        $this->assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            $this->assertContains($column->getAttributes()['name'], $expectedColumns);

            if ($column->getAttributes()['name'] === $builder->tree()->columnName()) {
                $this->assertEquals('integer', $column->getAttributes()['type']);
                $this->assertFalse($column->getAttributes()['nullable']);
                $this->assertTrue($column->getAttributes()['unsigned']);
                $this->assertNull($column->getAttributes()['default']);
            }

            if ($column->getAttributes()['name'] === $builder->parent()->columnName()) {
                $this->assertEquals('integer', $column->getAttributes()['type']);
                $this->assertTrue($column->getAttributes()['nullable']);
                $this->assertTrue($column->getAttributes()['unsigned']);
                $this->assertNull($column->getAttributes()['default']);
            }
        }
    }

    #[Test]
    public function columns_for_uuid_multi_tree(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::defaultMulti();
        $builder->tree()->setType(FieldType::UUID)->setColumnName('tid');

        (new Migrate($builder, $table))->buildColumns();

        $expectedColumns = $builder->columnsNames();

        $this->assertCount(count($expectedColumns), $table->getColumns());

        foreach ($table->getColumns() as $column) {
            $this->assertContains($column->getAttributes()['name'], $expectedColumns);

            if ($column->getAttributes()['name'] === $builder->tree()->columnName()) {
                $this->assertEquals('tid', $column->getAttributes()['name']);
                $this->assertEquals('uuid', $column->getAttributes()['type']);
                $this->assertFalse($column->getAttributes()['nullable']);
                $this->assertNull($column->getAttributes()['default']);
            }

            if ($column->getAttributes()['name'] === $builder->parent()->columnName()) {
                $this->assertEquals('integer', $column->getAttributes()['type']);
                $this->assertTrue($column->getAttributes()['nullable']);
                $this->assertTrue($column->getAttributes()['unsigned']);
                $this->assertNull($column->getAttributes()['default']);
            }
        }
    }

    #[Test]
    public function drop_columns(): void
    {
        $table = $this->getBlueprint(self::$tableName);
        $builder = Builder::default();

        (new Migrate($builder, $table))->dropColumns();

        $cols = $table->getColumns();
        $this->assertCount(0, $cols);
    }
}
