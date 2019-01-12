<?php

namespace Tests\Unit\Services\Activity;

use Tests\TestCase;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Activity\Activity\DestroyActivity;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_activity()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
        ];


        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
        ]);

        (new DestroyActivity)->execute($request);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);
    }
}
