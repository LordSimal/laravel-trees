# Basic Usage

## Model with only one Tree (one root)

```php
<?php
namespace App\Models;

use LordSimal\LaravelTrees\Traits\UseTree;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use UseTree<static> */
    use UseTree;
}
```

## Model with several Trees

```php
<?php
namespace App\Models;

use LordSimal\LaravelTrees\Traits\UseTree;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use UseTree<static> */
    use UseTree;
    
    protected static function buildTree(): Builder
    {
        return Builder::defaultMulti();
    }
}
```
