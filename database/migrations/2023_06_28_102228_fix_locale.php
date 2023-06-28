<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private $locales = [
        0 => 'ca',
        1 => 'da',
        2 => 'de',
        3 => 'en',
        4 => 'es',
        5 => 'fr',
        6 => 'it',
        7 => 'no',
        8 => 'pl',
        9 => 'pt',
        10 => 'ru',
        11 => 'ro',
        12 => 'sv',
        13 => 'tr',
        14 => 'vi',
        15 => 'el',
        16 => 'he',
        17 => 'ur',
        18 => 'hi',
        19 => 'bn',
        20 => 'nl',
        21 => 'pa',
        22 => 'te',
        23 => 'ml',
        24 => 'zh',
        25 => 'ja',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                if (array_key_exists($user->locale, $this->locales)) {
                    $user->locale = $this->locales[$user->locale];
                    $user->save();
                }
            }
        });
    }
};
