<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\CollectionHelper;
use Illuminate\Support\Facades\App;

class CollectionHelperTest extends FeatureTestCase
{
    /** @test */
    public function sortByCollator_base()
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
    public function sortByCollator_macro()
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
    public function sortByCollator_callback()
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
    public function sortByCollator_default_collation()
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
    public function sortByCollator_french_collation()
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
    public function getCollator_french_collation()
    {
        $collator = CollectionHelper::getCollator('fr');

        $this->assertEquals($collator->getAttribute(\Collator::FRENCH_COLLATION), \Collator::ON);
        $this->assertEquals($collator->getLocale(\Locale::VALID_LOCALE), 'fr');
    }

    /** @test */
    public function group_by_items_property()
    {
        $object1 = (object) ['name' => 'John'];
        $object2 = (object) ['name' => 'Jack'];
        $object3 = (object) ['name' => 'John'];

        $collection = collect([
            $object1,
            $object2,
            $object3,
        ]);

        $collection = CollectionHelper::groupByItemsProperty($collection, 'name');

        $this->assertEquals(
            [
                'John' => [$object1, $object3],
                'Jack' => [$object2],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_maps_uuid()
    {
        $collection = collect();
        for ($i = 1; $i <= 3; $i++) {
            $uuid = new \stdClass();
            $uuid->uuid = $i;
            $collection->push($uuid);
        }

        $uuids = $collection->mapUuid();

        $this->assertEquals([1, 2, 3], $uuids);
    }
}
