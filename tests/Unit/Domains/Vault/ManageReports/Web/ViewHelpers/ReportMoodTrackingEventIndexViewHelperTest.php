<?php

namespace Tests\Unit\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportMoodTrackingEventIndexViewHelper;
use App\Models\Contact;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportMoodTrackingEventIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 10, 10));
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'rated_at' => '2018-01-01 00:00:00',
        ]);

        $array = ReportMoodTrackingEventIndexViewHelper::data($vault, $user, 2018);

        $this->assertEquals(
            1,
            $array['months']->toArray()[0]['id']
        );
        $this->assertEquals(
            'Jan',
            $array['months']->toArray()[0]['month_word']
        );
    }
}
