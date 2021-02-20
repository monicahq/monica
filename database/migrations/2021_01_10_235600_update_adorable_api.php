<?php

use Illuminate\Support\Str;
use App\Models\Contact\Contact;
use Illuminate\Database\Migrations\Migration;

class UpdateAdorableAPI extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $adorable_api = Str::finish(config('monica.adorable_api'), '/');

        Contact::without(['account', 'avatarPhoto', 'gender'])
            ->where('avatar_adorable_url', 'like', '%api.adorable.io%')
            ->chunk(1000, function ($contacts) use ($adorable_api) {
                foreach ($contacts as $contact) {
                    $contact->update([
                        'avatar_adorable_url' => Str::of($contact->avatar_adorable_url)->replace('https://api.adorable.io/avatars/', $adorable_api),
                    ]);
                }
            });
    }
}
