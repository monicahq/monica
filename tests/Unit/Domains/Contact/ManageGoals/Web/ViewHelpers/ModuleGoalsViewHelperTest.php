<?php

namespace Tests\Unit\Domains\Contact\ManageGoals\Web\ViewHelpers;

use App\Domains\Contact\ManageGoals\Web\ViewHelpers\ModuleGoalsViewHelper;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleGoalsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        Goal::factory()->create([
            'contact_id' => $contact->id,
            'active' => true,
        ]);
        Goal::factory()->count(2)->create([
            'contact_id' => $contact->id,
            'active' => false,
        ]);

        $array = ModuleGoalsViewHelper::data($contact);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('active_goals', $array);
        $this->assertArrayHasKey('inactive_goals_count', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            2,
            $array['inactive_goals_count']
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $goal = Goal::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $array = ModuleGoalsViewHelper::dto($contact, $goal);

        $this->assertEquals(
            $goal->id,
            $array['id']
        );
        $this->assertEquals(
            $goal->name,
            $array['name']
        );
        $this->assertEquals(
            $goal->active,
            $array['active']
        );
        $this->assertEquals(
            [
                'max_streak' => 1,
                'current_streak' => 0,
            ],
            $array['streaks_statistics']
        );
        $this->assertArrayHasKey(
            'last_7_days',
            $array
        );
        $this->assertEquals(
            [
                'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id,
                'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id,
                'streak_update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id.'/streaks',
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/goals/'.$goal->id,
            ],
            $array['url']
        );
    }
}
