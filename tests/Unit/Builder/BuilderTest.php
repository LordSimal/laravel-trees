<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Unit\Builder;

use LordSimal\LaravelTrees\Config\Attribute;
use LordSimal\LaravelTrees\Config\AttributeType;
use LordSimal\LaravelTrees\Config\Builder;
use LordSimal\LaravelTrees\Tests\AbstractTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class BuilderTest extends AbstractTestCase
{
    #[Test]
    public function create_builder(): void
    {
        $builder = new Builder();
        $builder
            ->setAttributes(
                new Attribute(AttributeType::Left),
                new Attribute(AttributeType::Right),
                new Attribute(AttributeType::Level),
                new Attribute(AttributeType::Parent),
            );

        $this->assertEquals(AttributeType::Left->value, (string) $builder->left());
        $this->assertEquals(AttributeType::Right->value, (string) $builder->right());
        $this->assertEquals(AttributeType::Parent->value, (string) $builder->parent());
        $this->assertEquals(AttributeType::Level->value, (string) $builder->level());

        $this->assertEquals(
            [
                AttributeType::Left->value,
                AttributeType::Right->value,
                AttributeType::Level->value,
                AttributeType::Parent->value,
            ],
            $builder->columnsNames()
        );
    }

    #[Test]
    public function create_builder_for_multi_tree(): void
    {
        $builder = Builder::defaultMulti();
        $this->assertNotNull($builder->tree());

        $this->assertEquals(AttributeType::Right->value, (string) $builder->right());
        $this->assertEquals(AttributeType::Parent->value, (string) $builder->parent());
        $this->assertEquals(AttributeType::Level->value, (string) $builder->level());

        $this->assertEquals(
            [
                AttributeType::Left->value,
                AttributeType::Right->value,
                AttributeType::Level->value,
                AttributeType::Parent->value,
                AttributeType::Tree->value,
            ],
            $builder->columnsNames()
        );
    }

    #[Test]
    public function build_uno_config(): void
    {
        $builder = Builder::default();
        $config = $builder->build(new Category());

        $this->assertEquals(AttributeType::Left->value, (string) $config->left);
        $this->assertEquals(AttributeType::Right->value, (string) $config->right);
        $this->assertEquals(AttributeType::Parent->value, (string) $config->parent);
        $this->assertEquals(AttributeType::Level->value, (string) $config->level);
        $this->assertNull($config->tree);
    }

    #[Test]
    public function build_config(): void
    {
        $builder = Builder::defaultMulti();
        $config = $builder->build(new Category());

        $this->assertEquals(AttributeType::Left->value, (string) $config->left);
        $this->assertEquals(AttributeType::Right->value, (string) $config->right);
        $this->assertEquals(AttributeType::Parent->value, (string) $config->parent);
        $this->assertEquals(AttributeType::Level->value, (string) $config->level);
        $this->assertEquals(AttributeType::Tree->value, (string) $config->tree);
    }
}
