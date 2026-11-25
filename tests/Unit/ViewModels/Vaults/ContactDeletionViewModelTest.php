<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactDeletionViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDeletionViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_one_person_it_deletes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = $this->contact($user, $vault, 'Toby', 'Flenderson');

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [$toby]);

        $this->assertSame('Delete Toby Flenderson?', $viewModel->title());
        $this->assertSame('Toby Flenderson', $viewModel->names());
        $this->assertSame('Delete the contact', $viewModel->confirm());
        $this->assertNull($viewModel->keptLine());
        $this->assertSame([$toby->uuid], $viewModel->uuids());
    }

    #[Test]
    public function it_leaves_out_a_person_already_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $creed = $this->contact($user, $vault, 'Creed', 'Bratton');
        $creed->delete();
        $toby = $this->contact($user, $vault, 'Toby', 'Flenderson');

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [$creed, $toby]);

        $this->assertSame('Delete Toby Flenderson?', $viewModel->title());
        $this->assertSame([$toby->uuid], $viewModel->uuids());
        $this->assertNull($viewModel->keptLine());
    }

    #[Test]
    public function it_names_two_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [
            $this->contact($user, $vault, 'Toby', 'Flenderson'),
            $this->contact($user, $vault, 'Todd', 'Packer'),
        ]);

        $this->assertSame('Toby Flenderson and Todd Packer', $viewModel->names());
    }

    #[Test]
    public function it_names_up_to_three_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [
            $this->contact($user, $vault, 'Toby', 'Flenderson'),
            $this->contact($user, $vault, 'Todd', 'Packer'),
            $this->contact($user, $vault, 'Roy', 'Anderson'),
        ]);

        $this->assertSame('Delete 3 people?', $viewModel->title());
        $this->assertSame('Toby Flenderson, Todd Packer and Roy Anderson', $viewModel->names());
        $this->assertSame('Delete 3 people', $viewModel->confirm());
    }

    #[Test]
    public function it_names_two_people_and_counts_the_others(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [
            $this->contact($user, $vault, 'Toby', 'Flenderson'),
            $this->contact($user, $vault, 'Todd', 'Packer'),
            $this->contact($user, $vault, 'Roy', 'Anderson'),
            $this->contact($user, $vault, 'Jan', 'Levinson'),
            $this->contact($user, $vault, 'David', 'Wallace'),
        ]);

        $this->assertSame('Delete 5 people?', $viewModel->title());
        $this->assertSame('Toby Flenderson, Todd Packer and 3 others', $viewModel->names());
    }

    #[Test]
    public function it_says_the_contact_representing_the_user_stays(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [$me, $todd]);

        $this->assertTrue($viewModel->holdsAnybodyToDelete());
        $this->assertSame('Delete Todd Packer?', $viewModel->title());
        $this->assertSame([$todd->uuid], $viewModel->uuids());
        $this->assertSame('This is the contact that represents you, and it stays. Everything on the record is written from its point of view.', $viewModel->keptLine());
    }

    #[Test]
    public function it_holds_nobody_to_delete_when_only_the_contact_representing_the_user_is_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $viewModel = new ContactDeletionViewModel(user: $user, vault: $vault, contacts: [$me]);

        $this->assertFalse($viewModel->holdsAnybodyToDelete());
        $this->assertNotNull($viewModel->keptLine());
    }

    private function contact(User $user, Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
