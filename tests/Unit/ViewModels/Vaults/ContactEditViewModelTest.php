<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactEditViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactEditViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_template_the_contact_is_rendered_from_and_links_to_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin staff']);
        $version = StructureVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);
        $michael = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael);

        $this->assertTrue($template->is($viewModel->template()));
        $this->assertSame('Dunder Mifflin staff', $viewModel->template()->name);
        $this->assertSame(route('settings.templateBuilder.index', $template), $viewModel->templateUrl());
    }

    #[Test]
    public function it_names_no_template_once_the_template_is_gone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $dwight->structureVersion->structure->owner->delete();
        $dwight->refresh();

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $dwight);

        $this->assertNull($viewModel->template());
        $this->assertNull($viewModel->templateUrl());
    }
}
