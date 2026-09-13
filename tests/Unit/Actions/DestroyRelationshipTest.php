<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyRelationship;
use App\Enums\RelationshipTypeEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_relationship(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        new DestroyRelationship(
            user: $user,
            vault: $vault,
            relationship: $relationship,
        )->execute();

        $this->assertSoftDeleted($relationship);
    }

    #[Test]
    public function it_leaves_both_people_standing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $judy = Contact::query()->findOrFail($relationship->contact_a_id);
        $ross = Contact::query()->findOrFail($relationship->contact_b_id);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();

        $this->assertModelExists($judy);
        $this->assertModelExists($ross);
    }

    #[Test]
    public function it_leaves_standing_a_record_that_exists_only_because_this_link_named_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $ross = Contact::query()->findOrFail($relationship->contact_b_id);
        $ross->update(['is_partial' => true]);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();

        $this->assertModelExists($ross);
    }

    #[Test]
    public function it_takes_the_link_off_both_sides_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $judy = Contact::query()->findOrFail($relationship->contact_a_id);
        $ross = Contact::query()->findOrFail($relationship->contact_b_id);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();

        $this->assertCount(0, Relationship::query()->forContact($judy)->get());
        $this->assertCount(0, Relationship::query()->forContact($ross)->get());
    }

    #[Test]
    public function it_refuses_a_relationship_in_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $vault = $this->vaultOf($stranger);
        $relationship = $this->relationshipOn($vault, $stranger);

        $this->expectException(ModelNotFoundException::class);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();
    }

    #[Test]
    public function it_refuses_a_relationship_in_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $elsewhere = $this->vaultOf($user);
        $relationship = $this->relationshipOn($elsewhere, $user);

        $this->expectException(ModelNotFoundException::class);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();
    }

    #[Test]
    public function it_writes_the_removal_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        new DestroyRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipDeletion
            && $job->parameters['name'] === 'Judy Geller'
            && $job->parameters['other'] === 'Ross Geller');
    }

    private function relationshipOn(Vault $vault, User $user): Relationship
    {
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');

        return Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::ParentChild)->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
    }

    private function vaultOf(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'applies_to' => TemplateAppliesToEnum::Contact,
        ]);

        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $version->update(['status' => VersionStatusEnum::Published]);

        VaultDefaultTemplate::query()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $version->id,
        ]);

        return $vault->refresh();
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->firstOrFail();

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $default->template_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function typeOf(User $user, RelationshipTypeEnum $case): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => RelationshipCategory::query()->firstOrCreate(
                ['account_id' => $user->account_id, 'name' => 'Family'],
                ['position' => 1],
            )->id,
        ]);
    }
}
