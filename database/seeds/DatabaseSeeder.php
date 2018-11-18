<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        switch (\Illuminate\Support\Facades\App::environment()) {
            case 'local':
                $this->call(FakeUserTableSeeder::class);
                $this->call(CurrenciesTableSeeder::class);
            break;
            case 'testing':
                $this->call(FakeUserTableSeeder::class);
                $this->call(CurrenciesTableSeeder::class);
            break;
            case 'production':
                $this->call(CurrenciesTableSeeder::class);
            break;
        }
    }
}
