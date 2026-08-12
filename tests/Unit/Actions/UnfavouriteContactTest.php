<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UnfavouriteContact;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnfavouriteContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_contact_off_the_top_of_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->favourite()->create(['account_id' => $user->account_id]);

        $contact = new UnfavouriteContact(user: $user, contact: $contact)->execute();

        $this->assertFalse($contact->is_favourite);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => false]);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->favourite()->create();

        $this->expectException(ModelNotFoundException::class);

        new UnfavouriteContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_logs_the_unpinning(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->favourite()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        new UnfavouriteContact(user: $user, contact: $contact)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactUnfavourited
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Jim Halpert'],
        );
    }
}
