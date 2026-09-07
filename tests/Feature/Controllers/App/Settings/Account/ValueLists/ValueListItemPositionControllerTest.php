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

class ValueListItemPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_value_and_renumbers_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $salesman = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Salesman',
            'position' => 1,
        ]);
        $receptionist = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Receptionist',
            'position' => 2,
        ]);
        $manager = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Regional manager',
            'position' => 3,
        ]);

        $response = $this->actingAs($user)->put(route('settings.valueListItemPosition.update', $manager->id), [
            'position' => '1',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The order is saved.');

        $this->assertSame(1, $manager->refresh()->position);
        $this->assertSame(2, $salesman->refresh()->position);
        $this->assertSame(3, $receptionist->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.valueListItemPosition.update', $item->id), ['position' => '0'])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_refuses_to_move_a_value_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $item = ValueListItem::factory()->create([
            'account_id' => $dwight->account_id,
            'position' => 2,
        ]);

        $this->actingAs($michael)->put(route('settings.valueListItemPosition.update', $item->id), ['position' => '1'])
            ->assertNotFound();

        $this->assertSame(2, $item->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $this->put(route('settings.valueListItemPosition.update', 1), ['position' => '1'])
            ->assertRedirect(route('auth.login.new'));
    }
}
