<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\CarddavDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CarddavDocsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tells_davx5_users_to_store_groups_as_separate_vcards(): void
    {
        $viewModel = new CarddavDocsViewModel;

        $android = array_column($viewModel->clients(), 'steps', 'key')['android'];

        $this->assertContains(CarddavDocsViewModel::DAVX5_GROUPS, $android);
        $this->assertStringContainsString('**Groups are separate vCards**', $viewModel->markdown());
    }

    #[Test]
    public function it_says_nothing_about_groups_while_they_are_off_for_every_account(): void
    {
        Feature::for(null)->deactivate('groups');
        Feature::flushCache();

        $this->assertStringNotContainsString('Groups are separate vCards', new CarddavDocsViewModel()->markdown());
    }
}
