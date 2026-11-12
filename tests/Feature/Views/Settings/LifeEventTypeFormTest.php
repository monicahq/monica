<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Settings;

use App\Actions\CreateLifeEventType;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypeFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_type_or_retires_it_from_the_header(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.new'));

        $response->assertSee('role="switch"', false);
        $response->assertSee('name="is_active"', false);
        $response->assertSee('form="life-event-type-form"', false);
        $response->assertSee('aria-describedby="life-event-type-availability"', false);
        $response->assertSee('id="life-event-type-availability"', false);
        $response->assertSee('Offered when recording');
        $response->assertSee('Switch this off to retire the type.', false);
    }

    #[Test]
    public function it_edits_the_fields_on_the_same_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.new'));

        $response->assertSee('x-data="lifeEventTypeFields"', false);
        $response->assertSee('data-field-editor', false);
        $response->assertSee('@click="addBlock($el)"', false);
        $response->assertSee('data-block-template', false);
        $response->assertSee('data-field-template', false);
        $response->assertSee('data-choice-template', false);
    }

    #[Test]
    public function it_draws_a_row_with_the_settings_of_its_own_kind_and_keeps_the_others_in_templates(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: $category,
            name: 'Beet harvest',
        )->execute();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [[
                'key' => null,
                'group' => '',
                'label' => 'Variety',
                'type' => 'text',
                'is_required' => false,
                'help_text' => null,
                'settings' => ['placeholder' => 'Detroit Dark Red'],
                'choices' => [],
            ]],
        )->execute();

        $html = $this->actingAs($user)->get(route('settings.lifeEventTypes.edit', $type))->getContent();

        preg_match_all('/name="fields\[f\d+\]\[settings\]\[([a-z_]+)\]"/', $html, $sent);

        $this->assertEqualsCanonicalizing(['placeholder', 'max_length'], $sent[1]);
        $this->assertStringContainsString('@change="pickKind($el)"', $html);
        $this->assertStringContainsString('data-settings-template="number"', $html);
        $this->assertStringContainsString('data-mark-template="number"', $html);
    }

    #[Test]
    public function it_points_a_refused_delete_at_its_reason(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.edit', $type));

        $response->assertSee('aria-describedby="life-event-type-delete-reason"', false);
        $response->assertSee('id="life-event-type-delete-reason"', false);
        $response->assertSee('The type came with your account and stays.');
    }

    #[Test]
    public function it_never_shows_the_words_the_screen_exists_to_keep_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);

        $html = $this->actingAs($user)->get(route('settings.lifeEventTypes.edit', $type))->getContent();

        $start = strpos($html, '<h1');
        $screen = strip_tags(substr($html, (int) $start, (int) strpos($html, '</main>', (int) $start) - (int) $start));

        foreach (['template', 'structure', 'version'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $screen);
        }
    }
}
