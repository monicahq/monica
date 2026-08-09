<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_template_being_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.templateActivation.update', $template), ['active' => '0'])
            ->assertRedirect(route('settings.templates.index'))
            ->assertSessionHas('status');

        $this->assertFalse($template->fresh()->is_active);
    }

    #[Test]
    public function it_offers_a_template_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->inactive()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.templateActivation.update', $template), ['active' => '1'])
            ->assertRedirect(route('settings.templates.index'));

        $this->assertTrue($template->fresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($michael)->put(route('settings.templateActivation.update', $template), ['active' => '0'])
            ->assertNotFound();
    }
}
