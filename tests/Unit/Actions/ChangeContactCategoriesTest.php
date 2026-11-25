<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ChangeContactCategories;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeContactCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private ValueList $categories;

    private StructureVersion $version;

    private Field $field;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->categories = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $this->user->account_id]);

        $template = Template::factory()->create(['account_id' => $this->user->account_id]);
        $this->version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = SectionVersion::factory()->create(['account_id' => $this->user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $this->version->id,
            'section_version_id' => $section->id,
        ]);

        $this->field = Field::factory()->create([
            'section_version_id' => $section->id,
            'type' => FieldTypeEnum::Multiselect,
            'label' => 'Category',
            'value_list_id' => $this->categories->id,
        ]);
    }

    #[Test]
    public function it_replaces_the_categories_a_contact_held(): void
    {
        $family = $this->category('Family');
        $friends = $this->category('Friends');
        $work = $this->category('Work');
        $jim = $this->contact('Jim', 'Halpert');
        $this->hold($jim, [$family->id, $friends->id]);

        $outcome = new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $jim, 'categories' => [$work]],
        ])->execute();

        $this->assertSame([$jim->id], array_map(fn (Contact $contact): int => $contact->id, $outcome['changed']));
        $this->assertSame([$jim->uuid => [$family->id, $friends->id]], $outcome['previous']);
        $this->assertSame([$work->id], $jim->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());

        Queue::assertPushedOn('low', LogUserAction::class);
        $this->assertCount(1, Queue::pushed(LogUserAction::class));
    }

    #[Test]
    public function it_removes_every_category_when_none_is_given(): void
    {
        $family = $this->category('Family');
        $pam = $this->contact('Pam', 'Beesly');
        $this->hold($pam, [$family->id]);

        new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $pam, 'categories' => []],
        ])->execute();

        $this->assertSame([], $pam->heldCategories(reachesSensitiveFields: true)->all());
    }

    #[Test]
    public function it_leaves_a_contact_whose_template_has_no_category_field(): void
    {
        $work = $this->category('Work');
        $toby = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => 'Toby',
        ]);

        $outcome = new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $toby, 'categories' => [$work]],
        ])->execute();

        $this->assertSame([], $outcome['changed']);
        $this->assertSame('Their template has no category field.', $outcome['unchanged'][0]['reason']);
    }

    #[Test]
    public function it_leaves_a_contact_deleted_in_the_meantime_and_changes_the_others(): void
    {
        $work = $this->category('Work');
        $creed = $this->contact('Creed', 'Bratton');
        $creed->delete();
        $kevin = $this->contact('Kevin', 'Malone');

        $outcome = new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $creed, 'categories' => [$work]],
            ['contact' => $kevin, 'categories' => [$work]],
        ])->execute();

        $this->assertSame([$kevin->id], array_map(fn (Contact $contact): int => $contact->id, $outcome['changed']));
        $this->assertSame('That contact does not exist.', $outcome['unchanged'][0]['reason']);
        $this->assertSame([], $creed->heldCategories(reachesSensitiveFields: true)->all());
        $this->assertSame([$work->id], $kevin->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_leaves_a_required_category_in_place(): void
    {
        $this->field->update(['is_required' => true]);
        $family = $this->category('Family');
        $angela = $this->contact('Angela', 'Martin');
        $this->hold($angela, [$family->id]);

        $outcome = new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $angela, 'categories' => []],
        ])->execute();

        $this->assertSame('Their category is required.', $outcome['unchanged'][0]['reason']);
        $this->assertSame([$family->id], $angela->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_leaves_a_sensitive_category_to_a_caller_who_does_not_reach_sensitive_fields(): void
    {
        $this->field->update(['is_sensitive' => true]);
        $family = $this->category('Family');
        $work = $this->category('Work');
        $kevin = $this->contact('Kevin', 'Malone');
        $this->hold($kevin, [$family->id]);

        $outcome = new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $kevin, 'categories' => [$work]],
        ], reachesSensitiveFields: false)->execute();

        $this->assertSame('Their template has no category field.', $outcome['unchanged'][0]['reason']);
        $this->assertSame([$family->id], $kevin->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_refuses_an_item_that_is_not_a_contact_category(): void
    {
        $otherList = ValueList::factory()->create(['account_id' => $this->user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $this->user->account_id, 'value_list_id' => $otherList->id]);

        $this->expectException(InvalidArgumentException::class);

        new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $this->contact('Jim', 'Halpert'), 'categories' => [$item]],
        ])->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        $stamford = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $stamford->id]);

        $this->expectException(ModelNotFoundException::class);

        new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $karen, 'categories' => []],
        ])->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ChangeContactCategories(user: $this->user, vault: Vault::factory()->create(), assignments: [])->execute();
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        $elsewhere = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: [
            ['contact' => $this->contact('Jim', 'Halpert'), 'categories' => [$elsewhere]],
        ])->execute();
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_contacts(): void
    {
        $assignments = Contact::factory()->count(501)
            ->make(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id])
            ->map(fn (Contact $contact): array => ['contact' => $contact, 'categories' => []])
            ->all();

        $this->expectException(InvalidArgumentException::class);

        new ChangeContactCategories(user: $this->user, vault: $this->vault, assignments: $assignments)->execute();
    }

    private function category(string $label): ValueListItem
    {
        return ValueListItem::factory()->create([
            'account_id' => $this->user->account_id,
            'value_list_id' => $this->categories->id,
            'label' => $label,
            'position' => ValueListItem::query()->where('value_list_id', $this->categories->id)->count() + 1,
        ]);
    }

    private function contact(string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'structure_version_id' => $this->version->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    /** @param  list<int>  $itemIds */
    private function hold(Contact $contact, array $itemIds): void
    {
        FieldValue::factory()->create([
            'account_id' => $this->user->account_id,
            'valuable_id' => $contact->id,
            'field_id' => $this->field->id,
            'value_string' => null,
            'value_list_item_ids' => $itemIds,
        ]);
    }
}
