<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        switch (App::environment()) {
            case 'local':
                $this->call(FakeUserTableSeeder::class);
            break;
            case 'testing':
                $this->call(FakeUserTableSeeder::class);
            break;
            case 'production':
            break;
        }
    }
}
