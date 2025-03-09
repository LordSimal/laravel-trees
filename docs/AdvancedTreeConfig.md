# Advanced Tree Config

You can change or redefine default settings:

```php
<?php
namespace App\Models;

class Category extends Model
{
    use LordSimal\LaravelTrees\UseTree;
    
    protected static function buildTree(): Builder
    {
        return Builder::make()
            ->setAttributes(
                Attribute::make(AttributeType::Left),
                Attribute::make(AttributeType::Right),
                Attribute::make(AttributeType::Level),
                Attribute::make(AttributeType::Parent),
                // Attribute::make(AttributeType::Tree)->setColumnName('tid'),
            )
    }
}
```

or

```php
<?php
namespace App\Models;

class Category extends Model
{
    use LordSimal\LaravelTrees\UseTree;
    
    protected static function buildTree(): Builder
    {
        $builder = Builder::defaultMulti();
        $builder->tree()->setColumnName('tid');
        
        return $builder;
    }
}
```

## Setting up Primary Key and TreeId Type

- Primary Key: UUID
- TreeId: UUID

```php
<?php
namespace App\Models;

class Category extends Model
{
    use LordSimal\LaravelTrees\UseTree;
    
    protected $keyType = 'uuid';
    
    protected static function buildTree(): Builder
    {
        return Builder::defaultMulti()
            ->setAttribute(Attribute::make(AttributeType::Tree, FieldType::UUID))
            ->setAttribute(Attribute::make(AttributeType::Left)->setColumnName('custom_left'))
            ->setAttribute(Attribute::make(AttributeType::Right)->setColumnName('custom_right'))
            ->setAttribute(Attribute::make(AttributeType::Level)->setColumnName('custom_level'));
    }
}
```
