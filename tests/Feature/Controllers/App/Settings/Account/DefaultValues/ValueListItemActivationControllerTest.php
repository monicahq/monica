<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\DefaultValues;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_offering_a_value(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->used()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('settings.valueListItemActivation.update', $item->id), [
            'active' => '0',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The value is no longer offered.');

        $this->assertFalse($item->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_value_again_in_the_place_it_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'position' => 2,
        ]);

        $response = $this->actingAs($user)->put(route('settings.valueListItemActivation.update', $item->id), [
            'active' => '1',
        ]);

        $response->assertRedirect(route('settings.valueLists.index'));
        $response->assertSessionHas('status', 'The value is offered again.');

        $item->refresh();

        $this->assertTrue($item->is_active);
        $this->assertSame(2, $item->position);
    }

    #[Test]
    public function it_says_on_the_screen_that_a_value_is_no_longer_offered(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Salesman',
        ]);

        $this->actingAs($user)->get(route('settings.valueLists.index'))
            ->assertStatus(200)
            ->assertSee('no longer offered')
            ->assertSee('Offer again');
    }

    #[Test]
    public function it_refuses_an_answer_that_is_not_yes_or_no(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.valueListItemActivation.update', $item->id), ['active' => 'maybe'])
            ->assertSessionHasErrors('active');
    }

    #[Test]
    public function it_refuses_to_touch_a_value_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)->put(route('settings.valueListItemActivation.update', $item->id), ['active' => '0'])
            ->assertNotFound();

        $this->assertTrue($item->refresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $this->put(route('settings.valueListItemActivation.update', 1), ['active' => '0'])
            ->assertRedirect(route('auth.login.new'));
    }
}
