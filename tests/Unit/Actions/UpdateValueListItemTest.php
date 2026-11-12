<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueListItem;
use App\Enums\PhoneLabelEnum;
use App\Enums\UserActionEnum;
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
}
