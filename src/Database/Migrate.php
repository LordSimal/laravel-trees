<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Exceptions\InvalidConfigException;

final class Migrate
{
    public function __construct(protected Builder $builder, protected Blueprint $table) {}

    /**
     * @throws \LordSimal\LaravelTrees\Exceptions\InvalidConfigException
     */
    public static function columnsFromModel(Blueprint $table, Model|string $model): Builder
    {
        if (is_string($model)) {
            $instance = new $model;
        } else {
            $instance = $model;
        }

        if (method_exists($instance, 'getTreeBuilder')) {
            (new self($builder = $instance->getTreeBuilder(), $table))->buildColumns();

            return $builder;
        }

        throw new InvalidConfigException();
    }

    /**
     * Add default nested set columns to the table. Also create an index.
     */
    public function buildColumns(): void
    {
        foreach ($this->builder->columnsList() as $attribute) {
            $this->table->{$attribute->type()->value}($attribute->columnName())
                ->default($attribute->default())
                ->nullable($attribute->isNullable());
        }

        $this->buildIndexes();
    }

    private function buildIndexes(): void
    {
        foreach ($this->builder->columnIndexes() as $idx => $columns) {
            $this->buildIndex($idx, (array) $columns);
        }
    }

    private function buildIndex(string $name, array $columns): void
    {
        if ($this->builder->isMulti()) {
            $columns[] = $this->builder->tree()->columnName();
        }

        $this->table->index(
            $columns,
            $this->table->getTable()."_{$name}_idx"
        );
    }

    /**
     * Drop NestedSet columns.
     */
    public function dropColumns(): void
    {
        foreach ($this->builder->columnIndexes() as $idx => $columns) {
            $this->table->dropIndex($idx);
        }

        foreach ($this->builder->columnsNames() as $column) {
            $this->table->dropColumn($column);
        }
    }
}
