<?php

use App\Jobs\Avatars\CreateAvatarsForExistingContacts as CreateAvatarsForExistingContactsJob;
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
        CreateAvatarsForExistingContactsJob::dispatch();
    }
}
