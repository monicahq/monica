<?php

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
        Contact::without(['account', 'avatarPhoto', 'gender'])
            ->where('avatar_adorable_url', 'like', '%api.adorable.io%')
            ->chunk(1000, function ($contacts) {
                foreach ($contacts as $contact) {
                    $contact->avatar_adorable_url = str_replace('api.adorable.io/avatars', 'api.hello-avatar.com/adorables', $contact->avatar_adorable_url);
                    $contact->save();
                }
            });
    }
}
