<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Generators;

use Illuminate\Database\Eloquent\Model;
use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\FieldType;
use LordSimal\LaravelTrees\Exceptions\Exception;
use Symfony\Component\Uid\Uuid;

final readonly class GeneratorTreeId implements GeneratorTreeIdContract
{
    public function __construct(private Attribute $attribute) {}

    public function generateId(Model $model): string|int
    {
        return match (true) {
            $this->attribute->type()->isInteger() => $this->generateMaxId($model),
            $this->attribute->type() === FieldType::UUID => $this->generateUuid($model),
            default => throw new Exception('Not implemented'),
        };
    }

    protected function generateMaxId(Model $model): int
    {
        return (int) $model->max((string) $model->treeAttribute()) + 1;
    }

    protected function generateUuid(Model $model): string
    {
        return (string) Uuid::v7();
    }
}
