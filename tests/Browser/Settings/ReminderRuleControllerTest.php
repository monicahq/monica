<?php

namespace Tests\Browser\Settings;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Settings\SettingsPersonnalization;

class ReminderRuleControllerTest extends DuskTestCase
{
    public function test_it_displays_reminder_rules()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultReminderRulesTable();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsPersonnalization)
                    ->waitFor('.reminder-rules')
                    ->waitForText('7 days before')
                    ->waitForText('30 days before')
                    ->click('.reminder-rule-7')
                    ->pause(2000)
                    ->visit(new SettingsPersonnalization)
                    ->waitFor('.reminder-rules')
                    ->assertSeeIn('@reminder-rule-label', 'off');
        });
    }
}
