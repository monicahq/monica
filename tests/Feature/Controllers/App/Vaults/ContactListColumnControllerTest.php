<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListColumnControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_columns_the_list_may_hold(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']]);

        $this->actingAs($user)
            ->get(route('contactListColumns.edit', $vault))
            ->assertStatus(200)
            ->assertSee('Customise contact list')
            ->assertSee('Add a column')
            ->assertSee('Company')
            ->assertSee('x-data="contactListColumns(', escape: false)
            ->assertSee('action="'.route('contactListColumns.update', $vault).'"', escape: false);
    }

    #[Test]
    public function it_saves_the_columns_and_goes_back_to_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contactListColumns.update', $vault), [
                'columns' => [
                    ['source' => 'nickname', 'heading' => 'Known as', 'is_visible' => '1'],
                    ['source' => 'email', 'heading' => '', 'is_visible' => '0'],
                ],
            ])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('status', 'The list now shows your columns.');

        $this->assertDatabaseHas('contact_list_columns', ['vault_id' => $vault->id, 'source' => 'nickname', 'heading' => 'Known as', 'position' => 1]);
        $this->assertDatabaseHas('contact_list_columns', ['vault_id' => $vault->id, 'source' => 'email', 'is_visible' => false, 'position' => 2]);
    }

    #[Test]
    public function it_refuses_a_field_column_that_names_no_field(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contactListColumns.update', $vault), [
                'columns' => [['source' => 'field', 'is_visible' => '1']],
            ])
            ->assertSessionHasErrors(['columns.0.section', 'columns.0.field_key']);
    }

    #[Test]
    public function it_brings_a_refusal_of_the_action_back_to_the_form(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->from(route('contactListColumns.edit', $vault))
            ->put(route('contactListColumns.update', $vault), [
                'columns' => [
                    ['source' => 'phone', 'is_visible' => '1'],
                    ['source' => 'phone', 'is_visible' => '1'],
                ],
            ])
            ->assertRedirect(route('contactListColumns.edit', $vault))
            ->assertSessionHasErrors(['columns' => 'The list cannot show the same column twice.']);
    }
}
