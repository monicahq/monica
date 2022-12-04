<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\ModulePostsViewHelper;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Post;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModulePostsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'this is a title',
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'this is a title',
            'written_at' => '2022-01-01 00:00:00',
        ]);
        $post->contacts()->attach($contact->id);

        $collection = ModulePostsViewHelper::data($contact, $user);

        $this->assertEquals(
            1,
            count($collection)
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $post->id,
                    'title' => 'this is a title',
                    'journal' => [
                        'id' => $post->journal->id,
                        'name' => 'this is a title',
                        'url' => [
                            'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                        ],
                    ],
                    'written_at' => 'Jan 01, 2022',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id,
                    ],
                ],
            ],
            $collection->toArray()
        );
    }
}
