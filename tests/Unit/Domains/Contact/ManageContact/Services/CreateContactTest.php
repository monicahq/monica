<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_contact(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($regis, $regis->account, $vault);
    }

    /** @test */
    public function it_creates_a_contact_with_gender(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $gender = Gender::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, gender: $gender);
    }

    /** @test */
    public function it_creates_a_contact_with_pronoun(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $pronoun = Pronoun::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, pronoun: $pronoun);
    }

    /** @test */
    public function it_creates_a_contact_with_template(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $template = Template::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, template: $template);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateContact)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = Vault::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $this->executeService($regis, $account, $vault);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = $this->createAccount();
        $regis = User::factory()->create([
            'account_id' => $account->id,
        ]);
        $vault = Vault::factory()->create();
        $this->executeService($regis, $account, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($regis, $regis->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault, ?Gender $gender = null, ?Pronoun $pronoun = null, ?Template $template = null): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'first_name' => 'Ross',
            'listed' => false,
            'gender_id' => optional($gender)->id,
            'pronoun_id' => optional($pronoun)->id,
            'template_id' => optional($template)->id,
        ];

        $contact = (new CreateContact)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'vault_id' => $vault->id,
            'first_name' => 'Ross',
            'gender_id' => optional($gender)->id,
            'pronoun_id' => optional($pronoun)->id,
            'template_id' => optional($template)->id,
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_CREATED,
        ]);
    }
}
