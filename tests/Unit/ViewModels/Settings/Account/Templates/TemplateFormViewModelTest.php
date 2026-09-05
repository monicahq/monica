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
    public function it_says_whether_it_is_naming_or_renaming(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse((new TemplateFormViewModel(user: $user))->isEdit());
        $this->assertTrue((new TemplateFormViewModel(user: $user, template: $template))->isEdit());
    }

    #[Test]
    public function it_starts_the_fields_with_what_the_template_says(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Pet',
            'slug' => 'pet',
            'description' => 'Everything the vet asks',
        ]);

        $values = (new TemplateFormViewModel(user: $user, template: $template))->values();

        $this->assertEquals('Pet', $values['name']);
        $this->assertEquals('pet', $values['slug']);
        $this->assertEquals('Everything the vet asks', $values['description']);
    }

    #[Test]
    public function it_starts_a_new_template_empty_and_describing_a_contact(): void
    {
        $user = User::factory()->create();

        $values = (new TemplateFormViewModel(user: $user))->values();

        $this->assertEquals('', $values['name']);
        $this->assertEquals('contact', $values['appliesTo']);
    }

    #[Test]
    public function it_sends_cancel_back_to_where_the_form_was_opened_from(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'pet']);

        $this->assertEquals(
            route('settings.templates.index'),
            (new TemplateFormViewModel(user: $user))->cancelUrl(),
        );

        $this->assertEquals(
            route('settings.templateBuilder.index', 'pet'),
            (new TemplateFormViewModel(user: $user, template: $template))->cancelUrl(),
        );
    }

    #[Test]
    public function it_only_offers_the_slug_while_the_template_is_being_created(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue((new TemplateFormViewModel(user: $user))->slugIsEditable());
        $this->assertFalse((new TemplateFormViewModel(user: $user, template: $template))->slugIsEditable());
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
}
