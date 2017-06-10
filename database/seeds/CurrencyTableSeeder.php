<?php

use Illuminate\Database\Seeder;

class CurrencyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('currencies')->truncate();
      DB::table('currencies')->insert([ 'iso' => 'CAD', 'name' => 'Canadian Dollar', 'symbol'=>'$' ]);
      DB::table('currencies')->insert([ 'iso' => 'USD', 'name' => 'US Dollar', 'symbol'=>'$' ]);
      DB::table('currencies')->insert([ 'iso' => 'GBP', 'name' => 'British Pound', 'symbol'=>'£' ]);
      DB::table('currencies')->insert([ 'iso' => 'EUR', 'name' => 'Euro', 'symbol'=>'€' ]);
      DB::table('currencies')->insert([ 'iso' => 'RUB', 'name' => 'Russian Ruble', 'symbol'=>'₽' ]);
    }
}
