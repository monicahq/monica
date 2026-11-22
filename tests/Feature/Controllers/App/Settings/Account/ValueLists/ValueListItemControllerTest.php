<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ValueLists;

use App\Enums\ValueListEnum;
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
        $response->assertSessionHas('status');

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
    public function it_refuses_a_country_code_that_names_no_country(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        $this->actingAs($user)->post(route('settings.valueListItems.create', $list->id), ['label' => 'Japan', 'country_code' => 'XX1'])
            ->assertSessionHasErrors('country_code', errorBag: "newValue{$list->id}");
    }

    #[Test]
    public function it_gives_a_country_its_code_when_it_is_added_and_when_it_is_edited(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        $this->actingAs($user)->post(route('settings.valueListItems.create', $list->id), ['label' => 'Japan', 'country_code' => 'jp'])
            ->assertRedirect(route('settings.valueLists.index'));

        $japan = ValueListItem::query()->where('value_list_id', $list->id)->where('label', 'Japan')->firstOrFail();
        $this->assertSame('JP', $japan->country_code);

        $this->actingAs($user)->put(route('settings.valueListItems.update', $japan->id), ['label' => 'Japan', 'country_code' => 'KR'])
            ->assertRedirect(route('settings.valueLists.index'));

        $this->assertSame('KR', $japan->refresh()->country_code);
    }

    #[Test]
    public function it_returns_a_country_already_on_the_list_as_an_error_on_the_form(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'JP']);

        $this->actingAs($user)->post(route('settings.valueListItems.create', $list->id), ['label' => '', 'country_code' => 'JP'])
            ->assertSessionHasErrors('country_code', errorBag: "newValue{$list->id}");
    }

    #[Test]
    public function it_asks_for_a_country_code_on_the_countries_list_only(): void
    {
        $user = User::factory()->create();
        $countries = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $genders = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Genders->value]);
        $japan = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'label' => 'Japan', 'country_code' => 'JP']);
        $woman = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $genders->id]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertOk()
            ->assertSee('id="list-'.$countries->id.'-country-code"', escape: false)
            ->assertDontSee('id="list-'.$genders->id.'-country-code"', escape: false);

        $this->actingAs($user)->get(route('settings.valueListItems.edit', $japan->id))
            ->assertOk()
            ->assertSee('id="value-'.$japan->id.'-country-code"', escape: false)
            ->assertSee('value="JP"', escape: false);

        $this->actingAs($user)->get(route('settings.valueListItems.edit', $woman->id))
            ->assertOk()
            ->assertDontSee('id="value-'.$woman->id.'-country-code"', escape: false);
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
        $response->assertSessionHas('status');

        $item->refresh();

        $this->assertSame('Assistant to the regional manager', $item->label);
    }

    #[Test]
    public function it_deletes_a_value_nothing_uses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.valueListItems.destroy', $item->id));

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status');

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
        $response->assertSessionHas('error');

        $this->assertNotSoftDeleted($item);
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
