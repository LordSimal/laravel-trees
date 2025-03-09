<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Tests\Functional;

use Illuminate\Console\BufferedConsoleOutput;
use LordSimal\LaravelTrees\Table;
use LordSimal\LaravelTrees\Tests\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class TableTest extends AbstractFunctionalTreeTestCase
{
    /**
     * @return class-string<Category>
     */
    protected static function modelClass(): string
    {
        return Category::class;
    }

    #[Test]
    public function drawFromModel(): void
    {
        /** @var Category $root */
        $root = static::model(['title' => 'root node']);
        $root->makeRoot()->save();

        /** @var Category $node1 */
        $node1 = static::model(['title' => 'child 1']);
        $node1->appendTo($root)->save();

        /** @var Category $node11 */
        $node11 = static::model(['title' => 'child 1.1']);
        $node11->appendTo($node1)->save();

        /** @var Category $node111 */
        $node111 = static::model(['title' => 'child 1.1.1']);
        $node111->appendTo($node11)->save();

        /** @var Category $node12 */
        $node12 = static::model(['title' => 'child 1.2']);
        $node12->appendTo($node1)->save();

        $output = new BufferedConsoleOutput();
        Table::fromModel($root->refresh())
            ->setExtraColumns(
                ['title' => 'Label']
            )
            ->draw($output);

        $str = $output->fetch();

        self::assertNotEmpty($str);
    }

    //    #[Test]
    //    public function drawFromTree(): void
    //    {
    //        $root        = $this->createStructure();
    //        $structure1  = $this->createStructure($root);
    //        $structure2  = $this->createStructure($root);
    //        $structure11 = $this->createStructure($structure1);
    //        $structure12 = $this->createStructure($structure11);
    //
    //        $output = new BufferedConsoleOutput();
    //
    //        $collection = Structure::all();
    //
    //        Table::fromTree($collection->toTree())
    //            ->hideLevel()
    //            ->setExtraColumns(
    //                [
    //                    'title'                         => 'Label',
    //                    $root->leftAttribute()->name()  => 'Left',
    //                    $root->rightAttribute()->name() => 'Right',
    //                    $root->levelAttribute()->name() => 'Deep',
    //                ]
    //            )
    //            ->draw($output);
    //
    //        $str = $output->fetch();
    //
    //        self::assertNotEmpty($str);
    //    }

    //    #[Test]
    //    public function drawFromCollection(): void
    //    {
    //        $root        = $this->createStructure();
    //        $structure1  = $this->createStructure($root);
    //        $structure2  = $this->createStructure($root);
    //        $structure11 = $this->createStructure($structure1);
    //        $structure12 = $this->createStructure($structure11);
    //
    //        $output = new BufferedConsoleOutput();
    //
    //        $collection = Structure::all();
    //        $collection
    //            ->toOutput(
    //                [
    //                    'title'                         => 'Label',
    //                    $root->leftAttribute()->name()  => 'Left',
    //                    $root->rightAttribute()->name() => 'Right',
    //                ],
    //                $output,
    //                '...'
    //            );
    //
    //        $str = $output->fetch();
    //
    //        self::assertNotEmpty($str);
    //    }
}
