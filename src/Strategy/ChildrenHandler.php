<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Strategy;

use Illuminate\Database\Eloquent\Model;

interface ChildrenHandler
{
    public function handle(Model $model): void;
}
