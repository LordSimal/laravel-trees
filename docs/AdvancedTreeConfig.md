# Advanced Tree Config

You can change or redefine default settings:

```php
<?php
namespace App\Models;

use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\AttributeType;
use LordSimal\LaravelTrees\Config\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use LordSimal\LaravelTrees\UseTree;
    
    protected static function buildTree(): Builder
    {
        return new Builder()
            ->setAttributes(
                new Attribute(AttributeType::Left),
                new Attribute(AttributeType::Right),
                new Attribute(AttributeType::Level),
                new Attribute(AttributeType::Parent),
                // (new Attribute(AttributeType::Tree))->setColumnName('tid'),
            )
    }
}
```

or

```php
<?php
namespace App\Models;

use LordSimal\LaravelTrees\Config\Builder;
use Illuminate\Database\Eloquent\Model;

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

use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\AttributeType;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Config\FieldType;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use LordSimal\LaravelTrees\UseTree;
    
    protected $keyType = 'uuid';
    
    protected static function buildTree(): Builder
    {
        return Builder::defaultMulti()
            ->setAttribute(new Attribute(AttributeType::Tree, FieldType::UUID))
            ->setAttribute((new Attribute(AttributeType::Left))->setColumnName('custom_left'))
            ->setAttribute((new Attribute(AttributeType::Right))->setColumnName('custom_right'))
            ->setAttribute((new Attribute(AttributeType::Level))->setColumnName('custom_level'));
    }
}
```
