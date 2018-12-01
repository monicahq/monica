<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
