<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional\Tree\Uno;

use LordSimal\LaravelTrees\Tests\Functional\AbstractFunctionalTreeTestCase;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class NodeTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function get_bounds(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $this->assertIsArray($modelRoot->getBounds());
        $this->assertCount(4, $modelRoot->getBounds());
        $this->assertEquals(1, $modelRoot->getBounds()[0]);
        $this->assertEquals(2, $modelRoot->getBounds()[1]);
        $this->assertEquals(0, $modelRoot->getBounds()[2]);
        $this->assertEquals(null, $modelRoot->getBounds()[3]);
    }

    #[Test]
    public function get_node_bounds_by_model(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $data = $modelRoot->getNodeBounds($modelRoot);

        $this->assertIsArray($data);
        $this->assertCount(4, $data);
    }

    #[Test]
    public function get_node_bounds_by_id(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $data = $modelRoot->getNodeBounds($modelRoot->getKey());

        $this->assertIsArray($data);
        $this->assertCount(4, $data);
    }

    #[Test]
    public function get_node_data(): void
    {
        /** @var Category $modelRoot */
        $modelRoot = $this->model(['title' => 'root node']);
        $modelRoot->makeRoot()->save();

        $data = $modelRoot->getNodeData($modelRoot->id);
        $this->assertEquals(['lft' => 1, 'rgt' => 2, 'lvl' => 0, 'parent_id' => null], $data);
    }
}
