<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplateFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_new_template_empty_and_describing_a_contact(): void
    {
        $user = User::factory()->create();

        $values = (new TemplateFormViewModel(user: $user))->values();

        $this->assertEquals('', $values['name']);
        $this->assertEquals('contact', $values['appliesTo']);
    }

    #[Test]
    public function it_offers_the_kinds_of_record_the_account_invented(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);

        $viewModel = new TemplateFormViewModel(user: $user);

        $this->assertTrue($viewModel->hasEntityTypes());
        $this->assertEquals('Animals', $viewModel->entityTypes()[0]['name']);
    }

    #[Test]
    public function it_leaves_out_an_inactive_kind_of_record(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        $this->assertFalse((new TemplateFormViewModel(user: $user))->hasEntityTypes());
    }

    #[Test]
    public function it_keeps_the_kind_of_record_the_template_already_names(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id]);

        $viewModel = new TemplateFormViewModel(user: $user, template: $template);

        $this->assertTrue($viewModel->hasEntityTypes());
    }

    #[Test]
    public function it_leaves_out_the_kinds_of_record_of_another_account(): void
    {
        $michael = User::factory()->create();
        EntityType::factory()->create();

        $this->assertFalse((new TemplateFormViewModel(user: $michael))->hasEntityTypes());
    }

    #[Test]
    public function it_gives_the_reason_the_last_template_for_contacts_stays(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $this->assertSame(
            'This is the last template for contacts, and a new vault needs one, so it stays.',
            new TemplateFormViewModel(user: $user, template: $template)->deleteRefusal(),
        );
    }

    #[Test]
    public function it_gives_no_reason_when_the_template_can_be_deleted(): void
    {
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $this->assertNull(new TemplateFormViewModel(user: $user, template: $template)->deleteRefusal());
    }
}
