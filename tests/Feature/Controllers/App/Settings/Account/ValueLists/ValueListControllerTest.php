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

class ValueListControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_lists_of_the_account_and_the_values_in_them(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Job titles',
        ]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Assistant to the regional manager',
        ]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertStatus(200)
            ->assertSee('Job titles')
            ->assertSee('Assistant to the regional manager');
    }

    #[Test]
    public function it_creates_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.valueLists.create'), [
            'name' => 'Job titles',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The list is added.');

        $this->assertDatabaseHas('value_lists', [
            'account_id' => $user->account_id,
            'name' => 'Job titles',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_list_without_a_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.valueLists.create'), [])
            ->assertSessionHasErrors('name', errorBag: 'newList');
    }

    #[Test]
    public function it_renames_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Job titles',
        ]);

        $response = $this->actingAs($user)->put(route('settings.valueLists.update', $list->id), [
            'name' => 'Roles at Dunder Mifflin',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The list is renamed.');

        $list->refresh();

        $this->assertSame('Roles at Dunder Mifflin', $list->name);
    }

    #[Test]
    public function it_deletes_a_list_and_the_values_it_held(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
        ]);

        $response = $this->actingAs($user)->delete(route('settings.valueLists.destroy', $list->id));

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The list is deleted.');

        $this->assertSoftDeleted($list);
        $this->assertSoftDeleted($item);
    }

    #[Test]
    public function it_marks_deletion_unavailable_for_a_list_the_product_supplied(): void
    {
        $user = User::factory()->create();
        ValueList::factory()->supplied(ValueListEnum::Genders)->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertStatus(200)
            ->assertSee('Genders')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('This list stays.')
            ->assertDontSee('Delete “Genders”?', escape: false);
    }

    #[Test]
    public function it_refuses_to_delete_a_list_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->supplied(ValueListEnum::Genders)->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.valueLists.destroy', $list->id));

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('error', 'This list stays.');

        $this->assertNotSoftDeleted($list);
    }

    #[Test]
    public function it_refuses_to_touch_a_list_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)->put(route('settings.valueLists.update', $list->id), ['name' => 'Mine now'])
            ->assertNotFound();

        $this->actingAs($michael)->delete(route('settings.valueLists.destroy', $list->id))
            ->assertNotFound();

        $this->assertNotSoftDeleted($list);
    }
}
