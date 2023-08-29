<?php

namespace Tests\Unit\Domains\Contact\ManageCalls\Web\ViewHelpers;

use App\Domains\Contact\ManageCalls\Web\ViewHelpers\ModuleCallsViewHelper;
use App\Models\Call;
use App\Models\Contact;
use App\Models\Emotion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleCallsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $emotion = Emotion::factory()->create([
            'account_id' => $contact->vault->account_id,
        ]);

        $array = ModuleCallsViewHelper::data($contact, $user);

        $this->assertEquals(
            5,
            count($array)
        );

        $this->assertArrayHasKey('contact_name', $array);
        $this->assertArrayHasKey('emotions', $array);
        $this->assertArrayHasKey('calls', $array);
        $this->assertArrayHasKey('call_reason_types', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $emotion->id,
                    'name' => $emotion->name,
                    'type' => $emotion->type,
                ],
            ],
            $array['emotions']->toArray()
        );

        $this->assertEquals(
            $contact->name,
            $array['contact_name']
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/calls',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
            'called_at' => Carbon::now(),
            'description' => null,
        ]);

        $collection = ModuleCallsViewHelper::dto($contact, $call, $user);

        $this->assertEquals(
            [
                'id' => $call->id,
                'called_at' => 'Jan 01, 2018',
                'duration' => $call->duration,
                'description' => null,
                'who_initiated' => 'me',
                'type' => 'audio',
                'answered' => true,
                'emotion' => null,
                'reason' => [
                    'id' => $call->callReason->id,
                    'label' => $call->callReason->label,
                ],
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/calls/'.$call->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/calls/'.$call->id,
                ],
            ],
            $collection
        );
    }
}
