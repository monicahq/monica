<?php

namespace Tests\Unit\Domains\Contact\ManageCalls\Services;

use App\Domains\Contact\ManageCalls\Services\CreateCall;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\CallReason;
use App\Models\CallReasonType;
use App\Models\Contact;
use App\Models\Emotion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_call(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account->id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $callReason);
    }

    /** @test */
    public function it_creates_a_call_with_emotion(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account->id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);
        $emotion = Emotion::factory()->create([
            'account_id' => $regis->account->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $callReason, $emotion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateCall)->execute($request);
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
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $callReason);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $callReason);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = CallReasonType::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $callReason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $callReason);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, CallReason $reason, ?Emotion $emotion = null): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
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

        $call = (new CreateCall)->execute($request);

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
