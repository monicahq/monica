<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Helpers\RecordKind;
use App\ViewModels\Vaults\TemplateChangePreviewViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangePreviewViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_states_the_effects_and_names_the_button_after_what_can_move(): void
    {
        $fixture = AnimalsVault::build();

        $viewModel = new TemplateChangePreviewViewModel(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->horse,
            versionNumber: null,
            target: $fixture->pet,
        );

        $this->assertSame('Pet v3', $viewModel->toName());
        $this->assertSame('4 Animals can be moved.', $viewModel->readySentence());
        $this->assertSame([4, 2, 2, 0, 1, 0], array_column($viewModel->effects(), 'value'));
        $this->assertSame(
            ['Stable name · 2 Animals', 'Farrier visit · 1 Animal', 'Reminder “Farrier visit” · 1 Animal'],
            array_column($viewModel->details(), 'label'),
        );
        $this->assertSame('Move 4 Animals', $viewModel->moveLabel());
    }
}
