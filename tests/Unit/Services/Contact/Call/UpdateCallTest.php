<?php

namespace Tests\Unit\Services\Contact\Call;

use Tests\TestCase;
use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use App\Models\Instance\Emotion\Emotion;
use App\Services\Contact\Call\UpdateCall;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_call()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact,
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
            'content' => 'this is the content',
        ];

        $call = app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $call->contact_id,
            'account_id' => $call->contact->account_id,
            'content' => 'this is the content',
        ]);

        $this->assertInstanceOf(
            Call::class,
            $call
        );
    }

    /** @test */
    public function it_updates_a_call_and_who_called_info()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact,
            'account_id' => $contact->account_id,
            'contact_called' => 0,
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => 1,
        ];

        $call = app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $call->contact_id,
            'account_id' => $call->contact->account_id,
            'content' => 'this is the content',
            'contact_called' => 1,
        ]);
    }

    /** @test */
    public function it_updates_a_call_without_the_content()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact,
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
        ];

        $call = app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $call->contact_id,
            'account_id' => $call->contact->account_id,
            'content' => null,
        ]);
    }

    /**
     * Checks that it adds new emotions.
     */

    /** @test */
    public function it_updates_emotions()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
        ]);
        $emotion = factory(Emotion::class)->create([]);
        $emotion2 = factory(Emotion::class)->create([]);

        DB::table('emotion_call')->insert([
            'account_id' => $call->account_id,
            'contact_id' => $call->contact_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        $emotionArray = [];
        $emotionArray[] = $emotion->id;
        $emotionArray[] = $emotion2->id;

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => 1,
            'emotions' => $emotionArray,
        ];

        $call = app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion2->id,
        ]);
    }

    /**
     * Checks that it removes old emotion and add new emotions.
     */

    /** @test */
    public function it_deletes_and_updates_emotions()
    {
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
        ]);
        $emotion = factory(Emotion::class)->create([]);
        $emotion2 = factory(Emotion::class)->create([]);

        DB::table('emotion_call')->insert([
            'account_id' => $call->account_id,
            'contact_id' => $call->contact_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        DB::table('emotion_call')->insert([
            'account_id' => $call->account_id,
            'contact_id' => $call->contact_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion2->id,
        ]);

        $emotion3 = factory(Emotion::class)->create([]);
        $emotion4 = factory(Emotion::class)->create([]);
        $emotionArray = [];
        $emotionArray[] = $emotion3->id;
        $emotionArray[] = $emotion4->id;

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => 1,
            'emotions' => $emotionArray,
        ];

        $call = app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion3->id,
        ]);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion4->id,
        ]);

        $this->assertDatabaseMissing('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        $this->assertDatabaseMissing('emotion_call', [
            'contact_id' => $call->contact_id,
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion2->id,
        ]);
    }

    /** @test */
    public function it_updates_the_last_call_info()
    {
        $contact = factory(Contact::class)->create([
            'last_talked_to' => '1900-01-01 00:00:00',
        ]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact,
            'account_id' => $contact->account_id,
        ]);

        $date = now();

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
        ];

        app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_talked_to' => $date->toDateString(),
        ]);
    }

    /** @test */
    public function it_doesnt_update_the_last_call_info()
    {
        $contact = factory(Contact::class)->create([
            'last_talked_to' => '2200-01-01 00:00:00',
        ]);
        $call = factory(Call::class)->create([
            'contact_id' => $contact,
            'account_id' => $contact->account_id,
        ]);

        $date = now();

        $request = [
            'account_id' => $call->account_id,
            'call_id' => $call->id,
            'called_at' => now(),
        ];

        app(UpdateCall::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_talked_to' => '2200-01-01',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'called_at' => now(),
        ];

        $this->expectException(ValidationException::class);
        app(UpdateCall::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_call_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $call = factory(Call::class)->create();

        $request = [
            'account_id' => $account->id,
            'call_id' => $call->id,
            'called_at' => now(),
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateCall::class)->execute($request);
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
            'called_at' => now(),
            'content' => 'this is the content',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateCall::class)->execute($request);
    }
}
