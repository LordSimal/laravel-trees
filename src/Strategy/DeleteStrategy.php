<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Strategy;

use Illuminate\Database\Eloquent\Model;

interface DeleteStrategy
{
    public function handle(Model $model, bool $forceDelete): mixed;
}
