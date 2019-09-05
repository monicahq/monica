<?php

use App\Helpers\StringHelper;
use App\Models\Contact\Contact;
use Illuminate\Database\Migrations\Migration;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;
use App\Services\Contact\Avatar\GetAvatarsFromInternet;

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
        Contact::with('contactFields')->chunk(500, function ($contacts) {
            foreach ($contacts as $contact) {
                if (StringHelper::isNullOrWhitespace($contact->default_avatar_color)) {
                    $contact->setAvatarColor();
                    $contact->save();
                }
                $request = [
                    'contact_id' => $contact->id,
                ];

                app(GetAvatarsFromInternet::class)->execute($request);
                app(GenerateDefaultAvatar::class)->execute($request);
            }
        });
    }
}
