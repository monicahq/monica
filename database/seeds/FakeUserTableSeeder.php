<?php

use App\Account;
use Illuminate\Database\Seeder;

class FakeUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Account::create('John', 'Doe', 'admin@admin.com', 'admin');
        Account::create('Blank', 'State', 'blank@blank.com', 'blank');
    }
}
