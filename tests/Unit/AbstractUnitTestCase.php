<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit;

use Exception;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use LordSimal\LaravelTrees\Collection;
use LordSimal\LaravelTrees\Database\Migrate;
use LordSimal\LaravelTrees\Table;
use LordSimal\LaravelTrees\Tests\AbstractTestCase;
use LordSimal\LaravelTrees\Tests\Models\AbstractModel;
use LordSimal\LaravelTrees\Tests\Models\AbstractMultiModel;

abstract class AbstractUnitTestCase extends AbstractTestCase
{
    /**
     * @var class-string<AbstractModel|AbstractMultiModel>
     */
    protected static string $modelClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDB();
    }

    protected function setupDB(): void
    {
        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = app('db.connection');

        $connectionDriver = $connection->getDriverName();
        if ($connectionDriver === 'pgsql') {
            app('db.connection')->statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        }

        /** @var AbstractModel|AbstractMultiModel $model */
        $model = new static::$modelClass();

        $connection->getSchemaBuilder()->create(
            $model->getTable(),
            static function (Blueprint $table) use ($model, $connectionDriver) {
                switch ($connectionDriver) {
                    case 'pgsql':
                        $expression = new Expression('uuid_generate_v4()');
                        break;
                    case 'mysql':
                        $expression = new Expression('UUID()');
                        break;
                    default:
                        throw new Exception('Your DB driver ['.DB::getDriverName().'] does not supported');
                }

                if (in_array($model->getKeyType(), ['uuid', 'string'])) {
                    $table->uuid($model->getKeyName())->default($expression)->primary();
                } else {
                    $table->integerIncrements($model->getKeyName());
                }

                Migrate::columnsFromModel($table, $model);
                $table->string('title');
                $table->string('path')->nullable();
                $table->json('params')->default('{}');
                if ($model::isSoftDelete()) {
                    $table->softDeletes();
                }
            }
        );

        $connection->enableQueryLog();
    }

    /**
     * Helper for creating a tree
     *
     * @param  mixed  ...$childrenNodesCount
     * @return array The ID's of the created root nodes
     */
    protected function makeTree(AbstractModel|AbstractMultiModel|null $parentNode = null, ...$childrenNodesCount): array
    {
        if (! count($childrenNodesCount)) {
            return [];
        }

        $returningIds = [];
        $childrenCount = array_shift($childrenNodesCount);

        for ($i = 1; $i <= $childrenCount; $i++) {
            if (! $parentNode) {
                $node = new static::$modelClass(
                    [
                        'level' => 0,
                        'title' => "Root node $i",
                    ]
                );
                $node->makeRoot();
                $path = [$i];
            } else {
                $path = $parentNode->path;
                $path[] = $i;
                $pathStr = implode('.', $path);

                /** @var AbstractModel|AbstractMultiModel $node */
                $node = new static::$modelClass(['title' => "child $pathStr"]);
                $node->appendTo($parentNode);
            }

            $node->path = $path;
            $node->save();

            if (! $parentNode) {
                $returningIds[] = $node->getKey();
            }

            $this->makeTree($node, ...$childrenNodesCount);
        }

        return $returningIds;
    }

    protected function debugPrintTree(AbstractModel|AbstractMultiModel $parentNode): void
    {
        Table::fromModel($parentNode)
            ->setExtraColumns(
                [
                    'title' => 'Label',
                    (string) $parentNode->leftAttribute() => 'Left',
                    (string) $parentNode->rightAttribute() => 'Right',
                ]
            )
            ->draw();
    }

    protected function debugPrintCollection(Collection $collection): void
    {
        /** @var AbstractModel|AbstractMultiModel $node */
        $node = $collection->first();
        Table::fromTree($collection)
            ->setExtraColumns(
                [
                    'title' => 'Label',
                    (string) $node->leftAttribute() => 'Left',
                    (string) $node->rightAttribute() => 'Right',
                ]
            )
            ->draw();
    }

    protected static function sum(array $childMap, $level = null): int
    {
        if (! count($childMap)) {
            return 0;
        }

        if ($level === null) {
            $level = count($childMap);
        }

        $childMap = array_slice($childMap, 0, $level + 1);

        $res = array_reduce(
            $childMap,
            static function ($prev, $next) {
                if (! $prev) {
                    return [$next, $next];
                }

                [$prevCount, $total] = $prev;
                $prevTotal = $prevCount * $next;
                $total = $prevTotal + $total;

                return [$prevTotal, $total];
            }
        );

        return $res[1];
    }
}
