<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\TemplateAppliesToEnum;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_templates_of_the_account(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet', 'slug' => 'pet']);

        $this->actingAs($user)->get(route('settings.templates.index'))
            ->assertStatus(200)
            ->assertSee('Pet')
            ->assertSee('pet');
    }

    #[Test]
    public function it_leaves_out_the_templates_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Template::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Beet varieties']);

        $this->actingAs($michael)->get(route('settings.templates.index'))
            ->assertStatus(200)
            ->assertDontSee('Beet varieties');
    }

    #[Test]
    public function it_narrows_the_list_from_the_query_string(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet', 'slug' => 'pet']);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Company', 'slug' => 'company']);

        $this->actingAs($user)->get(route('settings.templates.index', ['query' => 'pet']))
            ->assertStatus(200)
            ->assertSee('Pet')
            ->assertDontSee('Company');
    }

    #[Test]
    public function it_shows_the_form_that_names_a_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.templates.new'))->assertStatus(200);
    }

    #[Test]
    public function it_creates_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templates.create'), [
            'name' => 'Pet',
            'applies_to' => 'contact',
            'description' => 'Everything the vet asks',
        ])->assertRedirect();

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Pet',
            'slug' => 'pet',
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_creates_a_template_that_describes_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.templates.create'), [
            'name' => 'Animal',
            'applies_to' => 'entity',
            'entity_type_id' => $entityType->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'applies_to' => TemplateAppliesToEnum::Entity->value,
            'entity_type_id' => $entityType->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_template_describing_an_entity_with_no_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templates.create'), [
            'name' => 'Animal',
            'applies_to' => 'entity',
        ])->assertSessionHasErrors('entity_type_id');
    }

    #[Test]
    public function it_refuses_a_kind_of_record_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $theirs = EntityType::factory()->create();

        $this->actingAs($michael)->post(route('settings.templates.create'), [
            'name' => 'Animal',
            'applies_to' => 'entity',
            'entity_type_id' => $theirs->id,
        ])->assertSessionHasErrors('entity_type_id');
    }

    #[Test]
    public function it_refuses_a_slug_another_template_already_uses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'pet']);

        $this->actingAs($user)->post(route('settings.templates.create'), [
            'name' => 'Pet',
            'applies_to' => 'contact',
            'slug' => 'pet',
        ])->assertSessionHasErrors('slug');
    }

    #[Test]
    public function it_refuses_a_template_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templates.create'), ['applies_to' => 'contact'])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_shows_the_form_that_renames_a_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet']);

        $this->actingAs($user)->get(route('settings.templates.edit', $template))
            ->assertStatus(200)
            ->assertSee('Pet');
    }

    #[Test]
    public function it_does_not_show_the_template_of_another_account(): void
    {
        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($michael)->get(route('settings.templates.edit', $template))->assertNotFound();
    }

    #[Test]
    public function it_renames_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Pet', 'slug' => 'pet']);

        $this->actingAs($user)->put(route('settings.templates.update', $template), [
            'name' => 'Companion animal',
        ])->assertRedirect(route('settings.templates.index'));

        $template->refresh();

        $this->assertEquals('Companion animal', $template->name);
        $this->assertEquals('pet', $template->slug);
    }

    #[Test]
    public function it_deletes_a_template_nothing_renders(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->delete(route('settings.templates.destroy', $template))
            ->assertRedirect(route('settings.templates.index'))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($template);
    }

    #[Test]
    public function it_explains_why_a_template_in_use_stays(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->used(12)->create(['template_id' => $template->id]);

        $this->actingAs($user)->delete(route('settings.templates.destroy', $template))
            ->assertRedirect(route('settings.templates.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($template);
    }

    #[Test]
    public function it_turns_a_visitor_away(): void
    {
        $this->get(route('settings.templates.index'))->assertRedirect(route('auth.login.new'));
    }
}
