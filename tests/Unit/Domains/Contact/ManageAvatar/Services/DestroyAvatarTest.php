<?php

namespace Tests\Unit\Domains\Contact\ManageAvatar\Services;

use App\Domains\Contact\ManageAvatar\Services\DestroyAvatar;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyAvatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_deletes_an_avatar_if_its_a_photo(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'type' => File::TYPE_AVATAR,
        ]);
        $contact->file_id = $file->id;
        $contact->save();

        $this->executeService($user, $user->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyAvatar)->execute($request);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, File $file): void
    {
        Queue::fake();
        Event::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];

        (new DestroyAvatar)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'file_id' => null,
        ]);
    }
}
