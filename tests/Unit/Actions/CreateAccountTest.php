<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccount;
use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedLocations;
use App\Jobs\SeedNotificationPreference;
use App\Jobs\SeedPossessionConditions;
use App\Jobs\SeedPossessionTypes;
use App\Jobs\SeedRelationshipTypes;
use App\Jobs\SupplyAnimalTemplate;
use App\Jobs\SupplyContactTemplates;
use App\Models\Field;
use App\Models\LifeEventType;
use App\Models\PreviewListEntry;
use App\Models\Template;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_account_and_its_first_user(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('accounts', [
            'id' => $user->account_id,
            'storage_used_in_bytes' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'welcomed_at' => null,
        ]);

        $this->assertTrue(Hash::check('thatswhatshesaid', $user->password));
        $this->assertTrue($user->is($user->account->user));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_gives_the_account_the_preferences_delivery_reads(): void
    {
        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael@dundermifflin.com',
            password: 'Th3W0rldsB3stB0ss',
            timezone: 'America/New_York',
        )->execute();

        $this->assertDatabaseHas('notification_preferences', [
            'account_id' => $user->account_id,
            'quiet_hours_are_enabled' => false,
            'delivery_mode' => 'immediate',
            'timezone' => 'America/New_York',
        ]);
    }

    #[Test]
    public function it_gives_the_account_a_vault_to_put_contacts_in(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $vault = $user->account->vaults()->sole();

        $this->assertSame(1, $vault->position);
        $this->assertTrue($vault->is_default);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultCreation
                && $job->vault->id === $vault->id,
        );
    }

    #[Test]
    public function it_gives_the_first_vault_the_contact_standing_for_the_new_user(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Pam',
            lastName: 'Beesly',
            email: 'pam.beesly@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $template = Template::query()->where('account_id', $user->account_id)->where('slug', DefaultTemplateEnum::BasicContact->value)->sole();
        $contact = $user->account->vaults()->sole()->contacts()->sole();

        $this->assertTrue($contact->is_me);
        $this->assertSame('Pam', $contact->first_name);
        $this->assertSame('Beesly', $contact->last_name);
        $this->assertSame($template->structure->published_version_id, $contact->structure_version_id);
    }

    #[Test]
    public function it_supplies_the_four_contact_templates_and_keeps_basic_contact_the_default(): void
    {
        $user = new CreateAccount(
            firstName: 'Jim',
            lastName: 'Halpert',
            email: 'jim.halpert@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $slugs = Template::query()->where('account_id', $user->account_id)->pluck('slug')->all();

        foreach (DefaultTemplateEnum::cases() as $supplied) {
            $this->assertContains($supplied->value, $slugs);
        }

        $basicContact = Template::query()->where('account_id', $user->account_id)->where('slug', DefaultTemplateEnum::BasicContact->value)->sole();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $user->account->vaults()->sole()->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $basicContact->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_asks_for_the_rest_of_the_seeding_in_the_order_it_has_to_happen_in(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        Queue::assertPushedWithChain(SeedNotificationPreference::class, [
            new SeedLifeEventCategories(user: $user),
            new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal),
            new SupplyAnimalTemplate(user: $user),
            new CreateDefaultLifeEventTypes(user: $user),
            new SeedRelationshipTypes(user: $user),
            new SeedPossessionTypes(user: $user),
            new SeedPossessionConditions(user: $user),
            new SeedLocations(user: $user),
            new SupplyContactTemplates(user: $user),
        ]);

        Queue::assertPushedOn(
            queue: 'high',
            job: SeedNotificationPreference::class,
            callback: fn (SeedNotificationPreference $job): bool => $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_asks_for_the_life_event_types_only_once_the_categories_they_are_filed_under_exist(): void
    {
        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $types = LifeEventType::query()->where('account_id', $user->account_id)->get();

        $this->assertCount(40, $types);
        $this->assertTrue($types->every(fn (LifeEventType $type): bool => $type->life_event_category_id !== null));
    }

    #[Test]
    public function it_asks_for_the_template_only_once_the_account_it_belongs_to_exists(): void
    {
        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $template = Template::query()->where('account_id', $user->account_id)->where('slug', 'basic-contact')->sole();

        $this->assertTrue($template->is_system);
        $this->assertNotNull($template->structure->published_version_id);
        $this->assertCount(3, $template->publishedVersion->pages);
        $this->assertCount(6, $template->publishedVersion->assembly);
        $this->assertEquals(
            'contact_categories',
            Field::query()->firstWhere('key', 'category')->valueList->key,
        );
    }

    #[Test]
    public function it_gives_the_account_the_storage_of_the_free_offer(): void
    {
        Queue::fake();
        config(['monica.free_storage_limit_in_bytes' => 4096]);

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertSame(4096, $user->account->storage_limit_in_bytes);
        $this->assertTrue($user->account->isMetered());
    }

    #[Test]
    public function it_never_meters_an_account_on_a_self_hosted_instance(): void
    {
        Queue::fake();
        config(['monica.self_hosted' => true]);

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertNull($user->account->storage_limit_in_bytes);
        $this->assertFalse($user->account->isMetered());
    }

    #[Test]
    public function it_creates_an_account_with_no_password_at_all(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
        )->execute();

        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->usesPasswordlessSignIn());
        $this->assertFalse($user->usesSingleSignOn());
        $this->assertDatabaseHas('users', ['email' => 'michael.scott@dundermifflin.com', 'password' => null]);
    }

    #[Test]
    public function it_creates_the_account_an_admission_was_given_for_with_its_address_already_verified(): void
    {
        Queue::fake();
        Notification::fake();

        $entry = PreviewListEntry::factory()->admitted('ryan-howard-token')->create([
            'email' => 'ryan.howard@dundermifflin.com',
        ]);

        $user = new CreateAccount(
            firstName: 'Ryan',
            lastName: 'Howard',
            email: 'somebody.else@dundermifflin.com',
            password: 'wuphf.com',
            previewListEntry: $entry,
        )->execute();

        $this->assertEquals('ryan.howard@dundermifflin.com', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());

        $entry->refresh();
        $this->assertNotNull($entry->registered_at);
        $this->assertNull($entry->token);
        $this->assertNull($entry->token_expires_at);

        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function it_asks_an_account_created_without_an_admission_to_verify_its_address(): void
    {
        Queue::fake();
        Notification::fake();

        $user = new CreateAccount(
            firstName: 'Ryan',
            lastName: 'Howard',
            email: 'ryan.howard@dundermifflin.com',
            password: 'wuphf.com',
        )->execute();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
