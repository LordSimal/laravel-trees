<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Strategy;

use Illuminate\Database\Eloquent\Model;

class MoveChildrenToParent implements ChildrenHandler
{
    public function handle(Model $model): void
    {
        $model->moveChildrenToParent();
    }
}
