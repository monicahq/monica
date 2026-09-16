<?php

declare(strict_types=1);

namespace Tests\Feature\Views\App\Settings\Security;

use App\Enums\ApiKeyClientEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyClientChoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_form_offers_a_choice_of_what_will_use_the_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertOk()
            ->assertSee('name="client"', false)
            ->assertSee('value="'.ApiKeyClientEnum::Script->value.'"', false)
            ->assertSee('value="'.ApiKeyClientEnum::Assistant->value.'"', false);
    }

    #[Test]
    public function the_choice_is_labelled_for_a_screen_reader(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertOk()
            ->assertSee('for="client"', false)
            ->assertSee(__('What will use this key'));
    }

    #[Test]
    public function the_row_says_in_words_which_kind_a_key_is(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $key->accessToken->client = ApiKeyClientEnum::Assistant->value;
        $key->accessToken->save();

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertOk()
            ->assertSee(__('AI Assistant'));
    }
}
