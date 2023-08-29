<?php

namespace Tests\Unit\Domains\Contact\ManageGoals\Web\ViewHelpers;

use App\Domains\Contact\ManageGoals\Web\ViewHelpers\GoalShowViewHelper;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class GoalShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $array = GoalShowViewHelper::data($contact, $user, $goal);

        $this->assertEquals(
            7,
            count($array)
        );

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('active', $array);
        $this->assertArrayHasKey('streaks_statistics', $array);
        $this->assertArrayHasKey('weeks', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id,
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id,
                'contact' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
            ],
            $array['url']
        );
    }
}
