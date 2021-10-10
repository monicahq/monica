<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Models\Contact\Note;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SearchHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function searching_for_contacts_returns_a_collection_with_pagination()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $searchResults = SearchHelper::searchContacts($contact->first_name, 'created_at')
            ->paginate(1);

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(1, $searchResults);
    }

    /** disabled for now */
    public function searching_with_notes()
    {
        $user = $this->signin();

        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'body' => 'we met on github and talked about monica',
        ]);

        $searchResults = SearchHelper::searchContacts('monica', 'created_at')
            ->paginate(1);

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(1, $searchResults);
    }

    /** disabled for now */
    public function searching_with_introduction_information()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_met_additional_info' => 'github',
        ]);
        $searchResults = SearchHelper::searchContacts($contact->first_met_additional_info, 'created_at')
            ->paginate(1);

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(1, $searchResults);
    }

    /** @test */
    public function searching_with_wrong_search_field()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $searchResults = SearchHelper::searchContacts('wrongsearchfield:1', 'created_at')
            ->paginate(1);

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(0, $searchResults);
    }
}
