<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\ReminderRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderRuleTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $user->account_id,
            'active' => true,
        ]);

        return [$user, $reminderRule];
    }

    /** @test */
    public function reminder_rule_index()
    {
        [$user, $reminderRule] = $this->fetchUser();

        $response = $this->get('/settings/personalization/reminderrules');

        $response->assertJsonFragment([
            'id' => $reminderRule->id,
            'active' => true,
        ]);
    }

    /** @test */
    public function reminder_rule_toggle()
    {
        [$user, $reminderRule] = $this->fetchUser();

        $response = $this->post('/settings/personalization/reminderrules/'.$reminderRule->id);

        $this->assertDatabaseHas('reminder_rules', [
            'id' => $reminderRule->id,
            'active' => 0,
        ]);
        $response->assertJsonFragment([
            'id' => $reminderRule->id,
            'active' => false,
        ]);
    }
}
