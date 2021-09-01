<?php

namespace Tests\Unit\Services\Contact\Call;

use Tests\TestCase;
use App\Models\Contact\Call;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use App\Models\Instance\Emotion\Emotion;
use App\Services\Contact\Call\DestroyCall;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_call()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'called_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
        ];

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
        ]);

        app(DestroyCall::class)->execute($request);

        $this->assertDatabaseMissing('calls', [
            'id' => $call->id,
        ]);
    }

    /** @test */
    public function it_removes_emotions()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
        ]);

        $emotion = factory(Emotion::class)->create([]);

        DB::table('emotion_call')->insert([
            'account_id' => $call->account_id,
            'contact_id' => $call->contact_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
        ];

        app(DestroyCall::class)->execute($request);

        $this->assertDatabaseMissing('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);
    }

    /** @test */
    public function it_updates_the_last_talked_to_information()
    {
        $contact = factory(Contact::class)->create([
            'last_talked_to' => '2008-01-01',
        ]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'called_at' => '2008-01-01',
        ]);
        $call2 = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'called_at' => '1990-01-01',
        ]);
        $call3 = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'called_at' => '1980-01-01',
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
        ];

        app(DestroyCall::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_talked_to' => '1990-01-01',
        ]);
    }

    /** @test */
    public function it_doesnt_update_the_last_talked_to_information()
    {
        $contact = factory(Contact::class)->create([
            'last_talked_to' => '2008-01-01',
        ]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'called_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
        ];

        app(DestroyCall::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_talked_to' => null,
        ]);
    }

    /** @test */
    public function it_fails_if_contact_is_archived()
    {
        $contact = factory(Contact::class)->state('archived')->create([]);
        $call = factory(Call::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyCall::class)->execute($request);
    }
}
