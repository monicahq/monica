<?php

use App\Models\User\User;
use App\Helpers\CountriesHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultTemperatureScale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('temperature_scale')->default('celsius')->change();
        });

        $country = null;
        $currentLocale = null;
        User::orderBy('locale')->chunkById(200, function ($users) use ($country, $currentLocale) {
            foreach ($users as $user) {
                if ($user->locale != $currentLocale || $country == null) {
                    $country = CountriesHelper::getCountryFromLocale($user->locale);
                    $currentLocale = $user->locale;
                }

                switch ($country->cca2) {
                    case 'US':
                    case 'BZ':
                    case 'KY':
                        $user->temperature_scale = 'fahrenheit';
                        break;
                    default:
                        $user->temperature_scale = 'celsius';
                        break;
                }

                $user->save();
            }
        });
    }
}
