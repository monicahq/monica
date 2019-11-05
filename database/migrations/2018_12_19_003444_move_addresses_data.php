<?php

use App\Models\Account\Place;
use App\Models\Contact\Address;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveAddressesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Address::chunk(200, function ($addresses) {
            foreach ($addresses as $address) {
                $place = new Place;
                $place->account_id = $address->account_id;
                $place->street = $address->street;
                $place->city = $address->city;
                $place->province = $address->province;
                $place->postal_code = $address->postal_code;
                $place->country = $address->country;
                $place->latitude = $address->latitude;
                $place->longitude = $address->longitude;
                $place->save();

                $address->place_id = $place->id;
                $address->save();
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('street');
            $table->dropColumn('city');
            $table->dropColumn('province');
            $table->dropColumn('postal_code');
            $table->dropColumn('country');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });
    }
}
