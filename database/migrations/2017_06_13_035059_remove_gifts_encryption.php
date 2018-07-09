<?php

use App\Models\Contact\Gift;
use Illuminate\Database\Migrations\Migration;

class RemoveGiftsEncryption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $gifts = Gift::all();
        foreach ($gifts as $gift) {

            // Uncomment the line below if you need to debug which row poses problem
            //echo $gift->id;
            if (! is_null($gift->name)) {
                $gift->name = decrypt($gift->name);
            }

            if (! is_null($gift->comment)) {
                $gift->comment = decrypt($gift->comment);
            }

            if (! is_null($gift->url)) {
                $gift->url = decrypt($gift->url);
            }

            $gift->save();
        }
    }
}
