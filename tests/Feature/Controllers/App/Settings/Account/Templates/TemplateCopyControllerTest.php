<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateCopyControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Copy of Contact',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_copies_a_supplied_template_into_an_ordinary_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->supplied()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template))->assertRedirect();

        $copy = Template::query()->where('account_id', $user->account_id)->where('is_system', false)->firstOrFail();

        $this->assertFalse($copy->is_system);
        $this->assertNull($copy->published_version_id);
    }

    #[Test]
    public function it_does_not_copy_the_template_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($michael)->post(route('settings.templateCopy.create', $template))->assertNotFound();
    }
}
