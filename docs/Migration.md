# Migration

To use Tree-structured Models you should change DB.

You can use Migrate Helper:

```php
<?php
return new class extends Migration {
    protected static $tableName = 'categories';

    public function up()
    {
        Schema::create(
            static::$tableName,
            static function (Blueprint $table) {
                $table->uuid()->primary();
                // ...
                // priority manner:
                \LordSimal\LaravelTrees\Database\Migrate::columnsFromModel($table, YourModel::class);
                
                // or custom for single tree:
                // (new \LordSimal\LaravelTrees\Database\Migrate(Builder::default(), $table))->buildColumns();
                
                // or custom for multi-tree:
                // (new \LordSimal\LaravelTrees\Database\Migrate(Builder::defaultMulti(), $table))->buildColumns();
                
                $table->timestamps();
                $table->softDeletes(); // if you need softDelete
            }
        );
    }
};
```
