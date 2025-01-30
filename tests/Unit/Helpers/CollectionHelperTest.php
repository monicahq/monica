<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CollectionHelper;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class CollectionHelperTest extends TestCase
{
    /** @test */
    public function it_sort_by_collator_base()
    {
        $collection = collect([
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ]);
        $collection = CollectionHelper::sortByCollator($collection, 'name');

        $this->assertEquals(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            array_values($collection->toArray())
        );
    }

    /** @test */
    public function it_sort_by_collator_macro()
    {
        $collection = collect([
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ]);
        $collection = $collection->sortByCollator('name');

        $this->assertEquals(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            array_values($collection->toArray())
        );
    }

    /** @test */
    public function it_sort_by_collator_callback()
    {
        $collection = collect([
            ['name' => 'a'],
            ['name' => 'c'],
            ['name' => 'b'],
        ]);
        $collection = $collection->sortByCollator(function ($item) {
            return $item['name'];
        });

        $this->assertEquals(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            array_values($collection->toArray())
        );
    }

    /** @test */
    public function it_sort_by_collator_default_collation()
    {
        App::setLocale('en');

        $collection = collect([
            ['name' => 'cote'],
            ['name' => 'côté'],
            ['name' => 'coté'],
            ['name' => 'côte'],
        ]);
        $collection = CollectionHelper::sortByCollator($collection, 'name');

        $this->assertEquals(
            [
                ['name' => 'cote'],
                ['name' => 'coté'],
                ['name' => 'côte'],
                ['name' => 'côté'],
            ],
            array_values($collection->toArray())
        );
    }

    /** @test */
    public function it_sort_by_collator_french_collation()
    {
        App::setLocale('fr');

        $collection = collect([
            ['name' => 'cote'],
            ['name' => 'côté'],
            ['name' => 'coté'],
            ['name' => 'côte'],
        ]);
        $collection = CollectionHelper::sortByCollator($collection, 'name');

        $this->assertEquals(
            [
                ['name' => 'cote'],
                ['name' => 'côte'],
                ['name' => 'coté'],
                ['name' => 'côté'],
            ],
            array_values($collection->toArray())
        );
    }

    /** @test */
    public function it_get_collator_french_collation()
    {
        $collator = CollectionHelper::getCollator('fr');

        $this->assertEquals($collator->getAttribute(\Collator::FRENCH_COLLATION), \Collator::ON);
        $this->assertEquals($collator->getLocale(\Locale::VALID_LOCALE), 'fr');
    }
}
