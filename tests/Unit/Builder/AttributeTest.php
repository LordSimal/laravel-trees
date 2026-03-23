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

        $this->assertEquals(AttributeType::Left, $attr->name());
        $this->assertEquals(AttributeType::Left->value, $attr->name()->value);
        $this->assertEquals(AttributeType::Left->value, (string) $attr);
        $this->assertNull($attr->default());
        $this->assertEquals(AttributeType::Left->value, $attr->columnName());
        $this->assertFalse($attr->isNullable());
        $this->assertEquals(FieldType::UnsignedInteger, $attr->type());
    }

    #[Test]
    public function change_column_name(): void
    {
        $attr = new Attribute(AttributeType::Left);
        $this->assertEquals(AttributeType::Left->value, $attr->columnName());

        $attr->setColumnName('test');
        $this->assertEquals('test', $attr->columnName());
    }

    #[Test]
    public function change_default(): void
    {
        $attr = new Attribute(AttributeType::Left);

        $this->assertNull($attr->default());

        $attr->setDefault(0);
        $this->assertEquals(0, $attr->default());
    }
}
