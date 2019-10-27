<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\CollectionHelper;
use Illuminate\Support\Facades\App;

class CollectionHelperTest extends FeatureTestCase
{
    public function test_sortByCollator_base()
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

    public function test_sortByCollator_macro()
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

    public function test_sortByCollator_callback()
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

    public function test_sortByCollator_default_collation()
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

    public function test_sortByCollator_french_collation()
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

    public function test_getCollator_french_collation()
    {
        $collator = CollectionHelper::getCollator('fr');

        $this->assertEquals($collator->getAttribute(\Collator::FRENCH_COLLATION), \Collator::ON);
        $this->assertEquals($collator->getLocale(\Locale::VALID_LOCALE), 'fr');
    }
}
