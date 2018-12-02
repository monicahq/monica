<?php

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
        Contact::chunk(200, function ($contacts) {
            foreach ($contacts as $contact) {
                $request = [
                    'contact_id' => $contact->id,
                ];

                $avatarService = new GetAvatarsFromInternet;
                $avatarService->execute($request);

                $avatarService = new GenerateDefaultAvatar;
                $avatarService->execute($request);
            }
        });
    }
}
