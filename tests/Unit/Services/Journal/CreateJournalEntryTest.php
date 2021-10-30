<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Tests\TestCase;
use App\Models\Journal\Entry;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Journal\CreateJournalEntry;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateJournalEntryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_journal_entry()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'title' => 'this is a title',
            'post' => 'this is a post',
            'date' => '2010-02-02',
        ];

        $entry = app(CreateJournalEntry::class)->execute($request);

        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'account_id' => $account->id,
            'title' => 'this is a title',
            'post' => 'this is a post',
            'written_at' => '2010-02-02',
        ]);

        $this->assertInstanceOf(
            Entry::class,
            $entry
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'happened_at' => now(),
        ];

        $this->expectException(ValidationException::class);

        app(CreateJournalEntry::class)->execute($request);
    }
}
