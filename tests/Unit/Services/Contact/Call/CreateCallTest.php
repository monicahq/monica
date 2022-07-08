<?php

namespace Tests\Unit\Services\Contact\Call;

use Tests\TestCase;
use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Instance\Emotion\Emotion;
use App\Services\Contact\Call\CreateCall;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_call()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
            'content' => 'this is the content',
        ];

        $call = app(CreateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'content' => 'this is the content',
            'contact_called' => 0,
        ]);

        $this->assertInstanceOf(
            Call::class,
            $call
        );
    }

    /** @test */
    public function it_stores_a_call_and_who_called_information()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => true,
        ];

        $call = app(CreateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'content' => 'this is the content',
            'contact_called' => 1,
        ]);
    }

    /** @test */
    public function it_adds_emotions()
    {
        $contact = factory(Contact::class)->create([]);
        $emotion = factory(Emotion::class)->create([]);
        $emotion2 = factory(Emotion::class)->create([]);

        $emotionArray = [];
        $emotionArray[] = $emotion->id;
        $emotionArray[] = $emotion2->id;

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => true,
            'emotions' => $emotionArray,
        ];

        $call = app(CreateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'content' => 'this is the content',
            'contact_called' => 1,
        ]);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion->id,
        ]);

        $this->assertDatabaseHas('emotion_call', [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'call_id' => $call->id,
            'emotion_id' => $emotion2->id,
        ]);
    }

    /** @test */
    public function it_fails_adding_emotions_when_emotion_is_unknown()
    {
        $contact = factory(Contact::class)->create([]);
        $emotionArray = [];
        $emotionArray[] = 1111111;

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
            'content' => 'this is the content',
            'contact_called' => true,
            'emotions' => $emotionArray,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(CreateCall::class)->execute($request);
    }

    /** @test */
    public function it_stores_a_call_without_the_content()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
        ];

        $call = app(CreateCall::class)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'content' => null,
        ]);
    }

    /** @test */
    public function it_updates_the_last_call_info()
    {
        $contact = factory(Contact::class)->create([
            'last_talked_to' => '1900-01-01 00:00:00',
        ]);

        $date = now();

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => $date,
        ];

        app(CreateCall::class)->execute($request);

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

        $date = now();

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => $date,
        ];

        app(CreateCall::class)->execute($request);

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
        app(CreateCall::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_contact_is_archived()
    {
        $contact = factory(Contact::class)->state('archived')->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'called_at' => now(),
            'content' => 'this is the content',
        ];

        $this->expectException(ValidationException::class);
        app(CreateCall::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'called_at' => now(),
            'content' => 'this is the content',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(CreateCall::class)->execute($request);
    }
}
