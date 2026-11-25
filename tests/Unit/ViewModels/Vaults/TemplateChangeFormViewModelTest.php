<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Helpers\RecordKind;
use App\ViewModels\Vaults\TemplateChangeFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangeFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_batch_and_offers_the_other_templates(): void
    {
        $fixture = AnimalsVault::build();

        $viewModel = new TemplateChangeFormViewModel(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->horse,
        );

        $this->assertSame('Horse · 4 Animals · Family vault', $viewModel->context());
        $this->assertSame(['All versions', 'v1'], array_column($viewModel->versionChoices(), 'label'));
        $this->assertSame(['Pet · v3', 'Bird · v1'], array_column($viewModel->destinations(), 'label'));
        $this->assertTrue($viewModel->destinations()[0]['isDefault']);
    }

    #[Test]
    public function it_locks_the_destination_and_takes_only_older_versions_for_an_update(): void
    {
        $fixture = AnimalsVault::build();

        $viewModel = new TemplateChangeFormViewModel(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->pet,
            target: $fixture->pet,
        );

        $this->assertSame('Update to v3', $viewModel->title());
        $this->assertSame('Pet · 3 Animals on older versions · Family vault', $viewModel->context());
        $this->assertSame(['All versions', 'v2'], array_column($viewModel->versionChoices(), 'label'));
        $this->assertSame(['Pet · v3'], array_column($viewModel->destinations(), 'label'));
    }
}
