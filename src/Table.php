<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees;

use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\Table as SymfonyTableHelper;
use Symfony\Component\Console\Output\OutputInterface;

final class Table
{
    protected string $offset = '    ';

    protected OutputInterface $output;

    protected ?Collection $collection = null;

    protected bool $showLevel = true;

    protected string $driverClass = SymfonyTableHelper::class;

    protected ?SymfonyTableHelper $driver = null;

    protected array $columns = [];

    public function draw(?OutputInterface $output = null): void
    {
        $this->setOutput($output);
        $this->render();
    }

    public function setOutput(?OutputInterface $output = null): static
    {
        $this->output = ($output ?? new BufferedConsoleOutput());

        return $this;
    }

    public function setOffset(string $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function setExtraColumns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function setCollection(Collection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function fromQuery(EloquentQueryBuilder $query): static
    {
        return $this->setCollection($query->get()->toTree());
    }

    public function hideLevel(): static
    {
        $this->showLevel = false;

        return $this;
    }

    protected function render(): void
    {
        $this->driver = class_exists($this->driverClass) ? new $this->driverClass($this->output) : null;

        $this->driver->setHeaders($this->getColumnLabel());

        if ($this->collection) {
            $this->addRow($this->collection);
        }

        $this->driver->setFooterTitle('Total nodes: '.$this->collection->totalCount());

        $this->driver->render();
    }

    protected function getExtraColumnLabel(): array
    {
        if (Arr::isAssoc($this->columns)) {
            return array_values($this->columns);
        }

        return $this->columns;
    }

    protected function getColumnLabel(): array
    {
        return [
            ...array_values($this->requiredColumnNames()),
            ...$this->getExtraColumnLabel(),
        ];
    }

    protected function getExtraColumnNames(): array
    {
        if (Arr::isAssoc($this->columns)) {
            return array_keys($this->columns);
        }

        return $this->columns;
    }

    protected function requiredColumnNames(): array
    {
        return array_merge($this->showLevel ? ['level' => 'Level'] : [], ['id' => 'ID']);
    }

    protected function getColumnNames(): array
    {
        static $list = [];

        if (! $list) {
            $list = $this->getExtraColumnNames();
        }

        return $list;
    }

    protected function getColumnValues(Model $node): array
    {
        $cols = $this->getColumnNames();

        return array_map(static fn ($col) => $node->$col, $cols);
    }

    private function addRow(Collection $tree): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $node */
        foreach ($tree as $node) {
            $id = $node->getKey();
            $values = $this->getColumnValues($node);

            $div = str_repeat($this->offset, $level = $node->levelValue());

            $hasChildren = ! $node->children->isEmpty();
            $sign = $hasChildren ? '+' : '-';

            $row = array_merge($this->showLevel ? [$level] : [], ["$div $sign $id", ...$values]);

            $this->driver->addRow($row);

            if ($hasChildren) {
                $this->addRow($node->children);
            }
        }
    }

    public static function fromModel(Model $model): self
    {
        return (new self())
            ->fromQuery($model->newNestedSetQuery()->descendantsQuery(null, true));
    }

    public static function fromTree(Collection $collection): self
    {
        return (new self())
            ->setCollection($collection);
    }
}
