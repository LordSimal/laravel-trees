<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ArchivedCategory extends Category
{
    use SoftDeletes;
}
