<?php

namespace Tests\Unit\Models;

use Tests\FeatureTestCase;
use App\Helpers\CountriesHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CountriesTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_all_countries_exist()
    {
        try {
            Schema::create('countries', function ($table) {
                $table->increments('id');
                $table->string('iso');
                $table->string('country');
            });
            (new \CountriesSeederTable)->run();

            foreach (DB::table('countries')->get() as $country) {
                $iso = \CountriesSeederTable::fixIso($country->iso);
                $cca2 = CountriesHelper::find($iso);

                $this->assertNotEmpty($cca2, 'Country not found for '.$iso);
            }
        } finally {
            Schema::dropIfExists('countries');
        }
    }
}
