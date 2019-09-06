<?php

use App\Models\Contact\Contact;
use App\Jobs\Avatars\GenerateDefaultAvatar;
use App\Jobs\Avatars\GetAvatarsFromInternet;
use Illuminate\Database\Migrations\Migration;

/**
 * This creates all the avatars (default, adorable and gravatars) for existing
 * contacts.
 */
class CreateAvatarsForExistingContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $delay = now();

        Contact::without(['account','avatarPhoto','gender'])
            ->chunk(400, function ($contacts) use ($delay) {
            foreach ($contacts as $contact) {
                GetAvatarsFromInternet::dispatch($contact->id)
                    ->delay($delay);
                GenerateDefaultAvatar::dispatch($contact)
                    ->delay($delay);
            }
            $delay = $delay->addMinutes(1);
        });
    }
}
