<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Avatars\UpdateAllGravatars;
use App\Jobs\Avatars\UpdateGravatar;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateAllGravatarsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_jobs_for_update_gravatars()
    {
        Queue::fake();

        $contacts = factory(Contact::class, 10)->create([
            'avatar_source' => 'gravatar',
        ]);

        (new UpdateAllGravatars)->handle();

        foreach ($contacts as $contact) {
            Queue::assertPushed(UpdateGravatar::class, function ($job) use ($contact) {
                return $job->contact->id === $contact->id;
            });
        }
    }
}
