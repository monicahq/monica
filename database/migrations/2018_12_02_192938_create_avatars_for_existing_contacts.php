<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Services\Contact\Avatar\GetAvatarsFromInternet;
use App\Models\Contact\Contact;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;

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
