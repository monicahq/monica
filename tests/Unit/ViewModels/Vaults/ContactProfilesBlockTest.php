<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultTemplateEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactProfilesBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactProfilesBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_profiles_naming_the_contact_with_what_they_are_to_it(): void
    {
        [$user, $vault] = $this->accountWithAnimals();
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Angela']);
        $sprinkles = $this->animal($user, $vault, 'Sprinkles');
        $bandit = $this->animal($user, $vault, 'Bandit');
        $this->caregiver($sprinkles, $angela, 'Owner');
        $this->caregiver($bandit, $angela, 'Sitter');

        $profiles = new ContactProfilesBlock(contact: $angela)->profiles();

        $this->assertSame(['Bandit', 'Sprinkles'], array_column($profiles, 'name'));
        $this->assertSame('People linked to the animal · Owner', $profiles[1]['detail']);
        $this->assertSame('Animal', $profiles[1]['type']);
        $this->assertSame(
            route('entities.show', ['vault' => $vault, 'entityType' => $sprinkles->entityType, 'entity' => $sprinkles]),
            $profiles[1]['url'],
        );
    }

    #[Test]
    public function it_leaves_out_a_profile_whose_value_a_change_of_template_set_aside(): void
    {
        [$user, $vault] = $this->accountWithAnimals();
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $sprinkles = $this->animal($user, $vault, 'Sprinkles');
        $this->caregiver($sprinkles, $angela, 'Owner')->update(['is_detached' => true]);

        $this->assertTrue(new ContactProfilesBlock(contact: $angela)->isEmpty());
    }

    /** @return array{0: User, 1: Vault} */
    private function accountWithAnimals(): array
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        new CreateDefaultValueLists(user: $user)->execute();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::Animal)->handle();

        return [$user, $vault];
    }

    private function animal(User $user, Vault $vault, string $name): Entity
    {
        return Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => EntityType::query()->where('account_id', $user->account_id)->sole()->id,
            'structure_version_id' => Template::query()->where('slug', DefaultTemplateEnum::Animal->value)->sole()->publishedVersion->id,
            'name' => $name,
        ]);
    }

    private function caregiver(Entity $animal, Contact $contact, string $role): FieldValue
    {
        $row = FieldValueRow::factory()->create([
            'account_id' => $animal->account_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $animal->id,
            'field_id' => $this->field(DefaultFieldEnum::Caregivers)->id,
        ]);

        FieldValue::factory()->create([
            'account_id' => $animal->account_id,
            'vault_id' => $animal->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $animal->id,
            'field_id' => $this->field(DefaultFieldEnum::CaregiverRole)->id,
            'field_value_row_id' => $row->id,
            'value_string' => $role,
        ]);

        return FieldValue::factory()->create([
            'account_id' => $animal->account_id,
            'vault_id' => $animal->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $animal->id,
            'field_id' => $this->field(DefaultFieldEnum::CaregiverPerson)->id,
            'field_value_row_id' => $row->id,
            'value_string' => null,
            'related_contact_id' => $contact->id,
        ]);
    }

    private function field(DefaultFieldEnum $field): Field
    {
        return Field::query()->where('key', $field->value)->sole();
    }
}
