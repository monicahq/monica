<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_form_that_writes_somebody_down(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('New contact')
            ->assertSee('First name')
            ->assertSee('Create the contact');
    }

    #[Test]
    public function it_offers_the_template_only_when_there_is_more_than_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertDontSee('name="template_version_id"', false);

        $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertSee('name="template_version_id"', false)
            ->assertSee('Work contact');
    }

    #[Test]
    public function it_says_nothing_about_itself_while_it_can_do_its_job(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Standard contact');

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertDontSee('Standard contact')
            ->assertDontSee('no template for new contacts to start from');
    }

    #[Test]
    public function it_says_so_when_the_vault_has_nothing_to_start_a_contact_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('This vault has no template for new contacts to start from.');
    }

    #[Test]
    public function it_writes_the_contact_down_and_goes_back_to_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'prefix' => 'Mr',
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
            'suffix' => 'Jr',
            'nickname' => 'Prison Mike',
            'maiden_name' => '',
            'name_order' => NameOrderEnum::FamilyFirst->value,
        ])
            ->assertRedirect(route('vaults.show', $vault))
            ->assertSessionHas('status', 'Scott Michael Gary is written down.');

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'prefix' => 'Mr',
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
            'suffix' => 'Jr',
            'nickname' => 'Prison Mike',
            'name_order' => NameOrderEnum::FamilyFirst->value,
            'is_partial' => false,
            'is_favourite' => false,
        ]);
    }

    #[Test]
    public function it_carries_the_two_flags_the_form_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Pam',
            'is_favourite' => '1',
            'is_partial' => '1',
        ]);

        $contact = Contact::query()->sole();

        $this->assertTrue($contact->is_favourite);
        $this->assertTrue($contact->is_partial);
    }

    #[Test]
    public function it_hands_back_the_empty_form_to_somebody_working_through_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Dwight',
            'keep_going' => '1',
        ])->assertRedirect(route('contacts.new', $vault));

        $this->assertDatabaseHas('contacts', ['vault_id' => $vault->id, 'first_name' => 'Dwight']);
    }

    #[Test]
    public function it_starts_the_contact_on_the_template_that_was_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $work = $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Jan',
            'template_version_id' => $work->published_version_id,
        ]);

        $this->assertEquals($work->published_version_id, Contact::query()->sole()->template_version_id);
    }

    #[Test]
    public function it_refuses_a_contact_with_no_first_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), ['last_name' => 'Scott'])
            ->assertSessionHasErrors('first_name');

        $this->assertEquals(0, Contact::query()->count());
    }

    #[Test]
    public function it_shows_what_was_typed_rather_than_reverting_a_rejected_form(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)
            ->from(route('contacts.new', $vault))
            ->post(route('contacts.create', $vault), [
                'first_name' => '',
                'last_name' => 'Scott',
                'nickname' => 'Prison Mike',
                'is_favourite' => '1',
            ])
            ->assertRedirect(route('contacts.new', $vault));

        $form = $this->actingAs($user)->get(route('contacts.new', $vault));

        $form->assertSee('value="Scott"', false)
            ->assertSee('value="Prison Mike"', false);

        $this->assertMatchesRegularExpression(
            '/id="is_favourite".*?checked/s',
            (string) $form->getContent(),
        );
    }

    #[Test]
    public function it_will_not_start_a_contact_from_a_version_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $vault = $this->vaultReadyForContacts($michael);
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)
            ->from(route('contacts.new', $vault))
            ->post(route('contacts.create', $vault), [
                'first_name' => '',
                'template_version_id' => $elsewhere->published_version_id,
            ]);

        $this->actingAs($michael)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertDontSee('Beet varieties')
            ->assertSee('no template for new contacts to start from');
    }

    #[Test]
    public function it_refuses_a_template_belonging_to_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $vault = $this->vaultReadyForContacts($michael);
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)->post(route('contacts.create', $vault), [
            'first_name' => 'Michael',
            'template_version_id' => $elsewhere->published_version_id,
        ])->assertSessionHasErrors('template_version_id');

        $this->assertEquals(0, Contact::query()->count());
    }

    #[Test]
    public function it_does_not_find_the_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create();

        $this->actingAs($user)->get(route('contacts.new', $elsewhere))->assertNotFound();
        $this->actingAs($user)->post(route('contacts.create', $elsewhere), ['first_name' => 'Michael'])
            ->assertNotFound();
    }

    private function vaultReadyForContacts(User $user, string $name = 'Basic contact'): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, $name);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $template->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
