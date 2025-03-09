<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Builder;

use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\AttributeType;
use LordSimal\LaravelTrees\Config\FieldType;
use LordSimal\LaravelTrees\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\Test;

class AttributeTest extends AbstractTestCase
{
    #[Test]
    public function create_attribute(): void
    {
        $attr = new Attribute(AttributeType::Left);

        static::assertEquals(AttributeType::Left, $attr->name());
        static::assertEquals(AttributeType::Left->value, $attr->name()->value);
        static::assertEquals(AttributeType::Left->value, (string) $attr);
        static::assertNull($attr->default());
        static::assertEquals(AttributeType::Left->value, $attr->columnName());
        static::assertFalse($attr->isNullable());
        static::assertEquals(FieldType::UnsignedInteger, $attr->type());
    }

    #[Test]
    public function change_column_name(): void
    {
        $attr = new Attribute(AttributeType::Left);
        static::assertEquals(AttributeType::Left->value, $attr->columnName());

        $attr->setColumnName('test');
        static::assertEquals('test', $attr->columnName());
    }

    #[Test]
    public function change_default(): void
    {
        $attr = new Attribute(AttributeType::Left);

        static::assertNull($attr->default());

        $attr->setDefault(0);
        static::assertEquals(0, $attr->default());
    }
}
