<?php

use App\Models\User\User;
use App\Models\Contact\Gender;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGenderType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('genders', function (Blueprint $table) {
            $table->string('type')->after('name')->nullable();
        });

        $woman = null;
        $man = null;
        $other = null;
        $womanEn = trans('app.gender_female', [], config('app.locale'));
        $manEn = trans('app.gender_male', [], config('app.locale'));
        $otherEn = trans('app.gender_none', [], config('app.locale'));

        $currentLocale = null;
        User::orderBy('locale')->chunkById(200, function ($users) use ($currentLocale, $woman, $man, $womanEn, $manEn, $other, $otherEn) {
            foreach ($users as $user) {
                if ($user->locale != $currentLocale) {
                    $currentLocale = $user->locale;
                    App::setLocale($currentLocale);
                    $woman = trans('app.gender_female');
                    $man = trans('app.gender_male');
                    $other = trans('app.gender_none');
                }

                $genders = Gender::where('account_id', $user->account_id)->get()->all();
                foreach ($genders as $gender) {
                    if ($gender->name == $woman || $gender->name == $womanEn) {
                        $gender->type = Gender::FEMALE;
                    } elseif ($gender->name == $man || $gender->name == $manEn) {
                        $gender->type = Gender::MALE;
                    } elseif ($gender->name == $other || $gender->name == $otherEn) {
                        $gender->type = Gender::OTHER;
                    } else {
                        $gender->type = Gender::UNKNOWN;
                    }
                    $gender->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('genders', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
