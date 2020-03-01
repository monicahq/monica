<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Jobs\Avatars\UpdateGravatar;
use Illuminate\Support\Facades\Queue;
use App\Jobs\Avatars\UpdateAllGravatars;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
