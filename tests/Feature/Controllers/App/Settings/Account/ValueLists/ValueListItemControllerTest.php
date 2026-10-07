<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ValueLists;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_value_to_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('settings.valueListItems.create', $list->id), [
            'label' => 'Regional manager',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The value is added.');

        $this->assertDatabaseHas('value_list_items', [
            'value_list_id' => $list->id,
            'label' => 'Regional manager',
            'position' => 1,
        ]);
    }

    #[Test]
    public function it_refuses_a_value_without_a_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.valueListItems.create', $list->id), [])
            ->assertSessionHasErrors('label', errorBag: "newValue{$list->id}");
    }

    #[Test]
    public function it_opens_the_form_renaming_one_value(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Assistant regional manager',
        ]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertDontSee('id="value-'.$item->id.'-label"', escape: false);

        $this->actingAs($user)->get(route('settings.valueListItems.edit', $item->id))
            ->assertStatus(200)
            ->assertSee('id="value-'.$item->id.'-label"', escape: false)
            ->assertSee('value="Assistant regional manager"', escape: false)
            ->assertSee('x-target="value-'.$item->id.'"', escape: false);
    }

    #[Test]
    public function it_renames_a_value(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'label' => 'Assistant regional manager',
        ]);

        $response = $this->actingAs($user)->put(route('settings.valueListItems.update', $item->id), [
            'label' => 'Assistant to the regional manager',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The value is renamed.');

        $item->refresh();

        $this->assertSame('Assistant to the regional manager', $item->label);
    }

    #[Test]
    public function it_refuses_a_rename_without_a_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.valueListItems.update', $item->id), [])
            ->assertSessionHasErrors('label', errorBag: "value{$item->id}");
    }

    #[Test]
    public function it_deletes_a_value_nothing_uses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.valueListItems.destroy', $item->id));

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The value is deleted.');

        $this->assertSoftDeleted($item);
    }

    #[Test]
    public function it_keeps_a_value_that_records_still_point_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->used()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.valueListItems.destroy', $item->id));

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('error', 'The value is in use and stays.');

        $this->assertNotSoftDeleted($item);
    }

    #[Test]
    public function it_shows_stop_offering_rather_than_delete_for_a_value_in_use(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->used()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Salesman',
        ]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertStatus(200)
            ->assertSee('Stop offering')
            ->assertSee('used 3 times');
    }

    #[Test]
    public function it_warns_in_a_card_before_a_value_is_deleted(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Salesman',
        ]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertStatus(200)
            ->assertSee('role="dialog"', escape: false)
            ->assertSee('Delete “Salesman”?', escape: false)
            ->assertSee('Nothing uses it yet, so it is deleted for good and stops being offered anywhere.');
    }

    #[Test]
    public function it_refuses_to_touch_a_value_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $dwight->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $dwight->account_id,
            'value_list_id' => $list->id,
        ]);

        $this->actingAs($michael)->post(route('settings.valueListItems.create', $list->id), ['label' => 'Mine now'])
            ->assertNotFound();

        $this->actingAs($michael)->get(route('settings.valueListItems.edit', $item->id))
            ->assertNotFound();

        $this->actingAs($michael)->put(route('settings.valueListItems.update', $item->id), ['label' => 'Mine now'])
            ->assertNotFound();

        $this->actingAs($michael)->delete(route('settings.valueListItems.destroy', $item->id))
            ->assertNotFound();

        $this->assertNotSoftDeleted($item);
    }
}
