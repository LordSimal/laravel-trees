<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use LordSimal\LaravelTrees\Database\Migrate;
use LordSimal\LaravelTrees\Tests\AbstractTestCase;

abstract class AbstractFunctionalTreeTestCase extends AbstractTestCase
{
    /**
     * @return class-string<\LordSimal\LaravelTrees\Tests\Models\AbstractModel|\LordSimal\LaravelTrees\Tests\Models\AbstractMultiModel>
     */
    abstract protected static function modelClass(): string;

    protected static function model(array $attributes = []): Model
    {
        $modelClass = static::modelClass();

        return new $modelClass($attributes);
    }

    private static function dbMigrate(): void
    {
        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = app('db.connection');

        $connectionDriver = $connection->getDriverName();
        if ($connectionDriver === 'pgsql') {
            app('db.connection')->statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        }

        $model = static::model();
        $treeBuilder = $model->getTreeBuilder();

        $connection->getSchemaBuilder()->create(
            $model->getTable(),
            static function (Blueprint $table) use ($treeBuilder, $model, $connectionDriver) {
                $expression = match ($connectionDriver) {
                    'pgsql' => new Expression('uuid_generate_v4()'),
                    'mysql' => new Expression('UUID()'),
                    default => throw new Exception('Your DB driver ['.DB::getDriverName().'] does not supported'),
                };

                if (in_array($model->getKeyType(), ['uuid', 'string'])) {
                    $table->uuid($model->getKeyName())->default($expression)->primary();
                } elseif ($model->getKeyType() === 'ulid') {
                    $table->ulid($model->getKeyName())->primary();
                } else {
                    $table->integerIncrements($model->getKeyName());
                }

                (new Migrate($treeBuilder, $table))->buildColumns();

                $table->string('title');
                $table->string('path')->nullable();
                $table->json('params')->default('{}');

                if (method_exists($model, 'isSoftDelete') && $model::isSoftDelete()) {
                    $table->softDeletes();
                }
            }
        );

        $connection->enableQueryLog();
    }

    protected function setUp(): void
    {
        parent::setUp();
        static::dbMigrate();
    }
}
