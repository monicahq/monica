<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetAsideValueControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_the_structure_stopped_asking_for(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $this->setAside($contact, 'Twisted it skiing');

        $this->actingAs($user)
            ->get(route('contacts.setAside.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('Twisted it skiing')
            ->assertSee('Nothing has been deleted', false);
    }

    #[Test]
    public function it_leaves_out_what_is_still_on_the_page(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id, 'label' => 'Knee']);
        FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => 'Still asked about',
        ]);

        $this->actingAs($user)
            ->get(route('contacts.setAside.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertDontSee('Still asked about');
    }

    #[Test]
    public function it_says_so_when_nothing_has_been_set_aside(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->get(route('contacts.setAside.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('No unused answers');
    }

    #[Test]
    public function it_refuses_somebody_in_another_vault(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('contacts.setAside.index', ['vault' => $elsewhere, 'contact' => $contact]))
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_in_another_account(): void
    {
        $contact = $this->contactOf(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('contacts.setAside.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertNotFound();
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }

    private function setAside(Contact $contact, string $value): FieldValue
    {
        $field = Field::factory()->create(['account_id' => $contact->account_id, 'label' => 'Knee']);

        return FieldValue::factory()->detached()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }
}
