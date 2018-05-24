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
                $this->call(ActivityTypesTableSeeder::class);
                $this->call(FakeUserTableSeeder::class);
            break;
            case 'testing':
                $this->call(ActivityTypesTableSeeder::class);
                $this->call(FakeUserTableSeeder::class);
            break;
            case 'production':
                $this->call(ActivityTypesTableSeeder::class);
            break;
        }
    }
}
