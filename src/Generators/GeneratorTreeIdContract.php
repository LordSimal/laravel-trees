<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Generators;

use Illuminate\Database\Eloquent\Model;

interface GeneratorTreeIdContract
{
    public function generateId(Model $model): string|int;
}
