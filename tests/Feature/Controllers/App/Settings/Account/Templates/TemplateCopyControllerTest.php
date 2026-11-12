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
    public function it_copies_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateCopy.create', $template))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Copy of Contact',
            'is_system' => false,
        ]);
    }
}
