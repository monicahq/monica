<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportFileViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_as_the_door_a_template_comes_through(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create());

        $this->assertFalse($viewModel->isForASection());
        $this->assertFalse($viewModel->isChecked());
        $this->assertFalse($viewModel->isRefused());
        $this->assertCount(3, $viewModel->steps());
    }

    #[Test]
    public function it_reads_as_the_door_a_section_comes_through(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id, 'structure_id' => $template->structure->id]);

        $viewModel = new ImportFileViewModel(
            user: $user,
            template: $template,
            page: $version->pages()->firstOrFail(),
        );

        $this->assertTrue($viewModel->isForASection());
        $this->assertStringContainsString('sections/import', $viewModel->checkUrl());
    }

    #[Test]
    public function it_carries_the_refusal_and_finds_nothing(): void
    {
        $viewModel = new ImportFileViewModel(user: User::factory()->create(), raw: 'nope', refusal: 'This is not readable as JSON.');

        $this->assertTrue($viewModel->isRefused());
        $this->assertEquals('This is not readable as JSON.', $viewModel->refusal());
        $this->assertEquals([], $viewModel->found());
    }
}
