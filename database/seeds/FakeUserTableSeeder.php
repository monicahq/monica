<?php

use App\Models\Account\Account;
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
        Account::createDefault('John', 'Doe', 'admin@admin.com', 'admin0', null, 'en');
        Account::createDefault('Blank', 'State', 'blank@blank.com', 'blank0', null, 'en');
    }
}
