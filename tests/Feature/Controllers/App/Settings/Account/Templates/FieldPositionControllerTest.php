<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_up_its_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $first = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);
        $second = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 2]);

        $this->actingAs($user)->put(route('settings.fieldPosition.update', [$template, $row, $second]), ['position' => 1])
            ->assertRedirect();

        $this->assertEquals(1, $second->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id]);

        $this->actingAs($user)->put(route('settings.fieldPosition.update', [$template, $row, $field]), ['position' => 'first'])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_refuses_a_field_that_is_not_in_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $elsewhere = Field::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.fieldPosition.update', [$template, $row, $elsewhere]), ['position' => 1])
            ->assertNotFound();
    }

    /**
     * @return array{0: Template, 1: TemplateVersionSection}
     */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
