<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueListItem;
use App\Enums\PhoneLabelEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_choice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'label' => 'Beet farmer',
        ]);

        $result = new UpdateValueListItem(
            user: $user,
            item: $item,
            label: 'Beet farm owner',
            description: 'Grows beets, sells beets',
            colour: '#AA151B',
            icon: 'sprout',
        )->execute();

        $this->assertInstanceOf(ValueListItem::class, $result);

        $item->refresh();

        $this->assertEquals('Beet farm owner', $item->label);
        $this->assertEquals('Grows beets, sells beets', $item->description);
        $this->assertEquals('#aa151b', $item->colour);
        $this->assertEquals('sprout', $item->icon);
    }

    #[Test]
    public function it_takes_a_supplied_choice_off_its_translation_key_when_it_is_renamed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->supplied(PhoneLabelEnum::Mobile)->create([
            'account_id' => $user->account_id,
        ]);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();

        $item->refresh();

        $this->assertNull($item->label_translation_key);
        $this->assertEquals('Cell', $item->label);
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();
    }

    #[Test]
    public function it_logs_what_changed_and_what_it_changed_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Phone labels',
        ]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Mobile',
        ]);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemUpdate
                && $job->isAbout($item)
                && $job->parameters === ['label' => 'Cell', 'name' => 'Phone labels']
                && $job->changes === [
                    'label' => ['before' => 'Mobile', 'after' => 'Cell'],
                ],
        );
    }

    #[Test]
    public function it_sends_the_records_holding_the_item_to_search_again_when_its_label_changes(): void
    {
        Queue::fake();
        $engine = $this->useRecordingSearchEngine();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'label' => 'Beet farmer']);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $farm = Entity::factory()->create(['vault_id' => $vault->id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        FieldValue::factory()->create(['valuable_type' => Contact::class, 'valuable_id' => $dwight->id, 'value_list_item_id' => $item->id]);
        FieldValue::factory()->create(['valuable_type' => Entity::class, 'valuable_id' => $farm->id, 'account_id' => $user->account_id, 'vault_id' => $vault->id, 'value_string' => null, 'value_list_item_ids' => [$item->id]]);
        $engine->forget();

        new UpdateValueListItem(user: $user, item: $item, label: 'Beet farm owner')->execute();

        $this->assertTrue($engine->wasSent($dwight));
        $this->assertTrue($engine->wasSent($farm));
        $this->assertFalse($engine->wasSent($jim));
    }

    #[Test]
    public function it_gives_a_country_its_code_and_logs_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Japan']);

        new UpdateValueListItem(user: $user, item: $item, label: 'Japan', countryCode: 'jp')->execute();

        $this->assertDatabaseHas('value_list_items', ['id' => $item->id, 'country_code' => 'JP']);
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->changes === ['country_code' => ['before' => null, 'after' => 'JP']],
        );
    }

    #[Test]
    public function it_refuses_a_country_code_on_a_list_that_holds_no_countries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Genders->value]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateValueListItem(user: $user, item: $item, label: 'Japan', countryCode: 'JP')->execute();
    }

    #[Test]
    public function it_names_a_country_by_its_code_alone_or_by_a_label_written_beside_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $picked = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Lithuania', 'country_code' => 'LT']);
        $named = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Schrute Farms', 'country_code' => null]);

        new UpdateValueListItem(user: $user, item: $picked, label: null, countryCode: 'LV')->execute();
        new UpdateValueListItem(user: $user, item: $named, label: 'Schrute Farms', countryCode: 'US')->execute();

        $this->assertDatabaseHas('value_list_items', ['id' => $picked->id, 'label' => null, 'country_code' => 'LV']);
        $this->assertDatabaseHas('value_list_items', ['id' => $named->id, 'label' => 'Schrute Farms', 'country_code' => 'US']);
    }

    #[Test]
    public function it_refuses_a_country_another_value_of_the_list_carries(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'JP']);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Nagoya', 'country_code' => null]);

        $this->expectException(ValidationException::class);

        new UpdateValueListItem(user: $user, item: $item, label: 'Nagoya', countryCode: 'JP')->execute();
    }
}
