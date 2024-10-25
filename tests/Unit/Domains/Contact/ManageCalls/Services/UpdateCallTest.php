<?php

namespace Tests\Unit\Domains\Contact\ManageCalls\Services;

use App\Domains\Contact\ManageCalls\Services\UpdateCall;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Call;
use App\Models\CallReason;
use App\Models\CallReasonType;
use App\Models\Contact;
use App\Models\Emotion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_task(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason);
    }

    /** @test */
    public function it_updates_a_task_with_emotion(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);
        $emotion = Emotion::factory()->create([
            'account_id' => $regis->account->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason, emotion: $emotion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateCall)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create(['call_reason_type_id' => $type->id]);

        $this->executeService($regis, $account, $vault, $contact, $call, $callReason);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create(['call_reason_type_id' => $type->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create(['call_reason_type_id' => $type->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason);
    }

    /** @test */
    public function it_fails_if_reminder_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create();
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create(['call_reason_type_id' => $type->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason);
    }

    /** @test */
    public function it_fails_if_call_reason_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $call = Call::factory()->create();
        $callReason = CallReason::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $call, $callReason);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Call $call, CallReason $reason, ?Emotion $emotion = null): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'call_id' => $call->id,
            'call_reason_id' => $reason->id,
            'called_at' => '1999-01-01',
            'duration' => 100,
            'type' => 'audio',
            'answered' => true,
            'who_initiated' => 'contact',
        ];

        if ($emotion !== null) {
            $request['emotion_id'] = $emotion->id;
        }

        $call = (new UpdateCall)->execute($request);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'contact_id' => $contact->id,
            'called_at' => '1999-01-01 00:00:00',
            'duration' => 100,
            'type' => 'audio',
            'answered' => true,
            'who_initiated' => 'contact',
            'emotion_id' => optional($emotion)->id,
        ]);
    }
}
