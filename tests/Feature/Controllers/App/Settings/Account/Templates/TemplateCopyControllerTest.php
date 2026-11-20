<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateCopyControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_copy_form(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        $this->actingAs($user)->get(route('settings.templateCopy.new', $template))
            ->assertOk()
            ->assertSee('Copy Contact')
            ->assertSee('Make the copy');
    }

    #[Test]
    public function it_copies_a_template_with_the_chosen_name_and_slug(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template), [
            'name' => 'Scranton branch',
            'slug' => 'scranton-branch',
        ])
            ->assertRedirect(route('settings.templateBuilder.index', 'scranton-branch'))
            ->assertSessionHas('status', 'The template is copied.');

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Scranton branch',
            'slug' => 'scranton-branch',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_slug_already_used_in_the_account(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'scranton-branch']);
        $count = Template::query()->count();

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template), [
            'name' => 'Scranton branch',
            'slug' => 'scranton-branch',
        ])->assertSessionHasErrors(['slug' => 'Another template in this account already uses that slug.']);

        $this->assertEquals($count, Template::query()->count());
    }

    #[Test]
    public function it_refuses_a_slug_held_by_a_deleted_template(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'scranton-branch'])->delete();

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template), [
            'name' => 'Scranton branch',
            'slug' => 'scranton-branch',
        ])->assertSessionHasErrors('slug');
    }

    #[Test]
    public function it_refuses_a_slug_with_capitals_or_spaces(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template), [
            'name' => 'Scranton branch',
            'slug' => 'Scranton Branch',
        ])->assertSessionHasErrors('slug');
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template), [
            'name' => '',
            'slug' => 'scranton-branch',
        ])->assertSessionHasErrors('name');
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        return $template;
    }
}
