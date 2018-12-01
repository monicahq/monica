<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
