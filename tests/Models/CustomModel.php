<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Models;

use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\AttributeType;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Config\FieldType;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 */
class CustomModel extends AbstractMultiModel
{
    public const TREE_ID = 'custom_tree_id';

    public const PARENT_ID = 'custom_parent_id';

    protected $keyType = 'string';

    protected $primaryKey = 'custom_id';

    protected $table = 'pages_uuid';

    protected $fillable = [
        'title',
    ];

    protected $hidden = [
        'lft',
        'rgt',
        'lvl',
        'custom_tree_id',
        'custom_parent_id',
    ];

    protected static function buildTree(): Builder
    {
        return Builder::defaultMulti()
            ->setAttribute(
                (new Attribute(AttributeType::Tree, FieldType::UUID))
                    ->setColumnName(self::TREE_ID),
            )->setAttribute(
                (new Attribute(AttributeType::Parent, FieldType::UUID))
                    ->setColumnName(self::PARENT_ID)
                    ->setNullable()
            )->setAttribute(
                (new Attribute(AttributeType::Left))
                    ->setColumnName('custom_left')
            )->setAttribute(
                (new Attribute(AttributeType::Right))
                    ->setColumnName('custom_right')
            )->setAttribute(
                (new Attribute(AttributeType::Level))
                    ->setColumnName('custom_level')
            );
    }

    public function generateTreeId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
