<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactEditViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactEditViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_an_upload_only_while_file_upload_is_on_for_the_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertTrue(new ContactEditViewModel(user: $user, vault: $vault, contact: $contact)->uploadsAreOn());

        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->assertFalse(new ContactEditViewModel(user: $user, vault: $vault, contact: $contact)->uploadsAreOn());
    }
}
