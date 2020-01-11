<?php

use Illuminate\Database\Migrations\Migration;
use App\Jobs\Avatars\CreateAvatarsForExistingContacts as CreateAvatarsForExistingContactsJob;

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
        CreateAvatarsForExistingContactsJob::dispatch();
    }
}
